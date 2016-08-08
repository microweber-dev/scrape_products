<?php

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/models.php';

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;
//use ScrapeProduct;
//use ScrapeProductSource;

function scrape_products_sources_per_page() {
	return app('request')->session()->get('scrape_products_sources_per_page', 5);
}

function scrape_products_sources_page() {
	return app('request')->session()->get('scrape_products_sources_page', 1);
}

function scrape_products_sources_filter() {
	return app('request')->session()->get('scrape_products_sources_filter');
}

api_expose_admin('scrape_products_proxy_page');
function scrape_products_proxy_page() {
	$url = app('request')->get('url');

	$client = new Client();
	$crawler = $client->request('GET', $url);

	$parsed = parse_url($url);
	$origin = $parsed['scheme'] . '://' . $parsed['host'] . (isset($parsed['port']) ? (':' . $parsed['port']) : '');

	$html = $crawler->html();
	$html = str_replace('<head>', ('<head><base href="' . $origin . '">'), $html);
	return $html;
}

api_expose_admin('scrape_products_sources_get');
function scrape_products_sources_get() {
	$perPage = scrape_products_sources_per_page();
	$page = scrape_products_sources_page();
	$filter = scrape_products_sources_filter();
	$q = ScrapeProductSource::take($perPage)->skip(($page - 1) * $perPage);
	if($filter) $q = $q->where('name', 'LIKE', "%$filter%");
	return $q->get();
}

api_expose_admin('scrape_products_sources_set_filter');
function scrape_products_sources_set_filter() {
	$filter = (int)app('request')->get('q');
	if(!$filter) $filter = '';
	app('request')->session()->put('scrape_products_sources_filter', $filter);
}

api_expose_admin('scrape_products_sources_set_page');
function scrape_products_sources_set_page() {
	$page = (int)app('request')->get('page');
	if(!$page || $page < 1) $page = 1;
	else {
		$pages = scrape_products_sources_pages_count();
		if($page > $pages) $page = $pages;
	}
	app('request')->session()->put('scrape_products_sources_page', $page);
}

function scrape_products_sources_pages_count() {
	$perPage = scrape_products_sources_per_page();
	$pages = ceil(ScrapeProductSource::count() / $perPage);
	if(!$pages || $pages < 1) $pages = 1;
	return $pages;
}

api_expose_admin('scrape_products_source_add');
function scrape_products_source_add() {
	$source = new ScrapeProductSource();
	$source->fill(app('request')->only(['name', 'uri', 'category_id']));
	$ok = $source->save();
	if(!$ok) {
		return array('error' => 'Error while adding source');
	}
}
api_expose_admin('scrape_products_source_remove');
function scrape_products_source_remove() {
	$source = ScrapeProductSource::find((int)app('request')->get('id'));
	if($source) $source->delete();
}

api_expose_admin('scrape_products_source_title');
function scrape_products_source_title() {
	$url = app('request')->get('uri');
	$client = new Client();
	$crawler = $client->request('GET', $url);
	$node = $crawler->filterXPath('//title');
	if($node) return $node->text();
}

api_expose_admin('scrape_products_source_refresh');
function scrape_products_source_refresh() {
	$sourceId = (int)app('request')->get('id');
	$source = ScrapeProductSource::find($sourceId);
	if(!$source) return;
	$categoryPage = get_page_for_category($source->category_id);
	$categories = mw()->category_manager->get_parents($source->category_id);
	array_unshift($categories, $source->category_id);
	$products = scrape_products_fetch_product_list($source->uri);
	foreach ($products as $product) {
		// Save scraped product data
		$productModel = ScrapeProduct::whereHash($product['hash'])->first();
		if(!$productModel) $productModel = new ScrapeProduct();
		$productModel->source_id = $sourceId;
		$productModel->hash = $product['hash'];
		$productModel->uri = $product['uri'];
		$productModel->name = $product['name'];
		$productModel->image = $product['image'];
		// Save content
		$parentId = isset($categoryPage['id']) ? $categoryPage['id'] : 1;
		$images = $productModel->image ? array($productModel->image) : null;
		$contentData = array(
			'id' => (int)$productModel->content_id,
			'title' => $productModel->name,
			'content' => $productModel->description,
			'parent' => $parentId,
			'categories' => $categories,
			'images' => $images,
			'download_remote_images' => true,
			'content_type' => 'product'
		);
		mw()->media_manager->download_remote_images = true;
		$contentId = save_content($contentData);
		// Save product
		$productModel->content_id = $contentId;
		$productModel->save();
	}
	return $products;
}

function scrape_products_fetch_product_list($url) {
	$client = new Client();
	$crawler = $client->request('GET', $url);
	$images = $crawler->filter('a img');
	$products = $images->each(function($node) {
		$a = null;
		foreach($node->parents() as $parent) {
			if($parent->nodeName == 'a') {
				$a = new Crawler($parent, $node->getBaseHref());
				break;
			}
		}
		if(!$a) return;
		$product = array(
			'image' => $node->image()->getUri(),
			'name' => trim($a->text()),
			'uri' => $a->link()->getUri()
		);
		$product['hash'] = hash('md5', implode('', $product));
		return $product;
	});
	return $products;
}

function scrape_products_scrape_details(ScrapeProduct $product, $threshold = 32) {
	$client = new Client();
	$image = $product->image;
	$name = $product->name;
	$crawler = $client->request('GET', $product->uri);
	$levenshteinMap = $crawler->filter('img')->each(function($node) use ($image) {
		$d = levenshtein($node->image()->getUri(), $image);
		return array('d' => $d, 'v' => $node);
	});
	usort($levenshteinMap, function($a, $b) { return $a['d'] - $b['d']; });
	$image = $levenshteinMap[0]['v'];
	$texts = $image->parents()->each(function($parent) use ($name) {
		$nodes = $parent->children()->reduce(function($node, $i) use ($name) {
			return mb_strpos($node->text(), $name);
		});
		if(!count($nodes)) return false;
		$text = trim($nodes->text());
		$text = mb_substr($text, mb_strpos($text, $name) + mb_strlen($name));
		return [$text, $nodes];
	});
	$theText = null;
	foreach ($texts as $text) {
		if(mb_strlen($text[0]) > $threshold) {
			$theText = $text;
			break;
		}
	}
	return $theText;
}