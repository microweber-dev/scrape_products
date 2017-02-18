<?php

namespace Modules;

use DB;

class ImportContent {

	public $queue;

	public function getSources() {
		return array(
			require('tools/virtualna.php'),
			require('tools/stantek.php'),
		);
	}

	public function getSource($name) {
		foreach($this->getSources() as $source) {
			if($source['name'] === $name) {
				return $source;
			}
		}
	}

	public function halt() {
		session_set('scrape_halt', true);
		file_put_contents(__DIR__.'/log.txt', '');
	}

	public function initQueue() {
		$queue = json_decode(get_option('Queue', 'ImportProducts'));
		if(!$queue) {
			$queue = [];
			$sourceKey = app('request')->input('source');
			$source = $this->getSource($sourceKey);
			if($sourceKey && $source) {
				$queue[] = [
					'tool' => $sourceKey,
					'saved' => 0,
					'total' => 0
				];
			}
			save_option([
				'option_key' => 'Queue',
				'option_group' => 'ImportProducts',
				'option_value' => json_encode($queue)
			]);
		}
        return $queue;
	}

    public function getQueue() {
	    $queue = json_decode(get_option('Queue', 'ImportProducts'));
        if(!$queue) return array();
        return $queue;
    }

    public function getQueueItem($tool) {
	    $queue = json_decode(get_option('Queue', 'ImportProducts'));
        if(!$queue) return false;
        foreach($queue as $item) {
            if($item['tool'] == $tool) {
                return $item;
            }
        }
    }

	public function start() {
		$saved = 0;
		$total = 0;
		session_set('scrape_halt', false);

        	$queue = $this->initQueue();
		if(!count($queue)) {
			return json_encode(['error' => 'Empty queue']);
		}

		$resume = app('request')->get('resume', 0);
		DB::transaction(function() use($queue, $saved, $total, $resume) {
			$source = $this->getSource(end($queue)->tool);
			$logFile = realpath(__DIR__.'/../cache/'. $source['name'] .'.txt');

			// Load cached source
			$xmlSrc = realpath(__DIR__.'/../cache/'. $source['name'] .'.xml');
			$xml = @simplexml_load_file($xmlSrc);
			if(!$xml) {
				// Fetch remote and cache
				$xml = @simplexml_load_file($source['remote']['src']);
				file_put_contents($xmlSrc, $xml->asXML());
			}
			if(!$xml) return ['error' => 'Error while fetching source'];

			$items = $xml->xpath($source['remote']['path']);
			$total = count($items);
			if($total < 1) return;

			mw()->media_manager->download_remote_images = $source['remote']['download'];

			// Init progress log
			file_put_contents($logFile, '0/1');
			if(0 == $resume) {
				$toDelete = get_content(['content_type' => 'product', 'no_limit' => 1]);
				if($toDelete) {
					// Purge content from DB
					$ids = array_map(function($c) { return $c['id']; }, $toDelete);
					mw()->content_manager_helpers->delete(['ids' => $ids, 'forever' => 1]);
				}
			}

			$error = 0;
			$processed = 0;
			$tStart = time();
			file_put_contents($logFile, '0/'.$total);

			foreach($items as $item) {
				// Check for halt
				if(session_get('scrape_halt') === true) {
					session_set('scrape_halt', false);
					$total = 'HALT';
					break;
				}
				// Account for resume offset
				$processed++;
				if($resume >= $processed) continue;
				// Map content data for source
				$contentData = call_user_func($source['mapContent'], $item);
				$contentData['download_remote_images'] = $source['remote']['download'];
				// Data fields
				$dataFields = $source['tag'];
				if($source['mapData'] && is_callable($source['mapData'])) {
					$mapped = call_user_func($source['mapData'], $item);
					$dataFields = array_merge($dataFields, $mapped);
				}
				if(count($dataFields)) {
					$contentData['data_fields'] = $dataFields;
				}
				// Taxonomy
				$shopPages = get_pages('is_shop=1');
				$itemCategory = call_user_func($source['mapCategory'], $item);
				$catId = get_categories('title='.$itemCategory);
				if(count($catId))
					$catId = $catId[0]['id'];
				if(0 >= $catId) {
					$catId = save_category([
						'title' => $itemCategory,
						'parent_page' => $shopPages[0]['id']
					]);
				}
				$contentData['categories'] = $catId;
				// Parent shop
				$contentData['parent'] = $shopPages[0]['id'];
				// Save content
				$contentId = save_content($contentData);
				// Log + estimate
				$tLapse = time() - $tStart;
				$eta = $saved > 0 ? ($tLapse / $saved) * ($total - $saved) : 0;
				$logData = $saved.'/'.$total.'/'.$error.'/'.(int)$eta;
				file_put_contents($logFile, $logData);
				if(!$contentId) {
					$error++;
					continue;
				}
				$saved++;
				// Custom fields
				if($source['mapFields'] && is_callable($source['mapFields'])) {
					$fields = call_user_func($source['mapFields'], $item);
					foreach($fields as $field) {
						$fieldData = array_merge($field, ['content_id' => $contentId]);
						save_custom_field($fieldData);
					}
				}
			}
			$logFile = str_replace(realpath(__DIR__.'/../'), '', $logFile);
		});
		return json_encode($queuedItem);
	}

}
