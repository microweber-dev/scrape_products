<?php return array(
    'name' => 'stantek',
    'remote' => [
        'src' => 'http://www.stantek.com/dicom.xml',
        'download' => false,
        'path' => '/pricelist/items/item'
    ],
    'tag' => ['Дистрибутор' => 'Стантек'],
    'mapContent' => function($item) {
        return array(
            'title' => (string)$item->name,
            'description' => (string)$item->description,
            'images' => [(string)$item->pic],
            'content_type' => 'product'
        );
    },
    'mapCategory' => function($item) {
        $cat = (string)$item->category;
        $map = require(__DIR__.'/stantek_catmap.php');
        // TODO: map categories
        return $cat;
    },
    'mapData' => function($item) {
        return array(['ID' => (int)$item->id]);
    },
    'mapFields' => function($item) {
        return array([
                'field_name' => 'Цена',
                'field_value' => (float)$item->price,
                'field_type' => 'price'
        ]);
    }
);