<?php

use Illuminate\Database\Eloquent\Model;

class ScrapeProduct extends Model {

	protected $table = 'scrape_products';

	protected $fillable = ['source_id', 'hash', 'uri', 'name', 'image', 'description', 'content_id', 'media_id'];

	public function source() {
		return $this->belongsTo('ScrapeProductSource', 'source_id');
	}

	public function content() {
		return $this->belongsTo('Content', 'content_id');
	}

	public function media() {
		return $this->belongsTo('Fields', 'media_id');
	}
}

class ScrapeProductSource extends Model {

	protected $table = 'scrape_products_sources';

	protected $fillable = ['name', 'uri', 'category_id'];

	public function products() {
		return $this->hasMany('ScrapeProduct', 'source_id', 'id');
	}
}