<?php $config = [
	'name' => 'Scrape Products',
	'author' => 'ash',
	'no_cache' => true,
	'ui' => false,
	'ui_admin' => true,
	'type' => 'content',
	'categories' => 'online shop',
	'position' => 6,
	'version' => '0.0.2',
  	'tables' => function() {
		if (!Schema::hasTable('scrape_products')) {
			Schema::create('scrape_products', function($table) {
				$table->bigIncrements('id');
				$table->bigInteger('source_id')->index();
				$table->string('hash')->unique();
				$table->string('uri')->index();
				$table->string('name')->index();
				$table->string('image')->nullable();
				$table->longText('description')->nullable();
				$table->bigInteger('content_id')->nullable();
				$table->bigInteger('media_id')->nullable();
				$table->timestamps();
			});
		}
		if (!Schema::hasTable('scrape_products_sources')) {
			Schema::create('scrape_products_sources', function($table) {
				$table->bigIncrements('id');
				$table->string('name');
				$table->string('uri')->index();
				$table->bigInteger('category_id')->index();
				$table->timestamps();
			});
		}
  }
];
