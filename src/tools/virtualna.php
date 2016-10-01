<?php return array(
    'name' => 'virtualna',
    'remote' => [
        'src' => 'https://www.virtualnazona.com/feed/?pc=2d03f229220acef2e723fe28b53cf56c',
        'download' => false,
        'path' => '/productsList/product'
    ],
    'tag' => ['Дистрибутор' => 'Виртуална Зона'],
    'mapContent' => function($item) {
        $images = [(string)$item->image];
        if($item->images && count($item->images->image)) {
            foreach($item->images->image as $image) {
                $images[] = (string)$image;
            }
        }
        $shopPages = get_pages('is_shop=1');
        return array(
            'parent' => $shopPages[0]['id'],
            'title' => (string)$item->product_name,
            'description' => (string)$item->description,
            'images' => $images,
            'content_type' => 'product'
        );
    },
    'mapCategory' => function($item) {
        $cat = (string)$item->category;
        $cat = preg_replace('/[^a-zA-Z0-9]+/', '', $cat);
        $cat = str_ireplace('филтър', '', $cat);
        $cat = trim($cat);
        return $cat;
    },
    'mapData' => function($item) {
        $data = ['ID' => (int)$item->product_id];
        if($item->features && count($item->features->feature)) {
            foreach($item->features->feature as $feature) {
                $key = (string)$feature->attributes()->title;
                $data[$key] = trim((string)$feature);
            }
        }
        return $data;
    },
    'mapFields' => function($item) {
        return array([
                'field_name' => 'Цена',
                'field_value' => (float)$item->price_dealer,
                'field_type' => 'price'
            ]);
    }
);