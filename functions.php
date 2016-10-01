<?php

require_once(__DIR__.'/src/ImportContent.php');

mw()->singleton('import_content', function() {
    return new \Modules\ImportContent();
});


api_expose_admin('import_count_products');
function import_count_products() {
	return \Content::where('content_type', 'product')->count();
}

api_expose_admin('import_halt');
function import_halt() {
	mw('import_content')->halt();
}

api_expose_admin('import_start');
function import_start() {
	$module = mw('import_content');
	$module->start();
	return json_encode([
		'saved' => $module->saved,
		'total' => $module->total
	]);
}
