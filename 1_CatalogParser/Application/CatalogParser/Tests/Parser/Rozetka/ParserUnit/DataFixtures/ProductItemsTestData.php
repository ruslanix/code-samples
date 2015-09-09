<?php

namespace Application\CatalogParser\Tests\Parser\Rozetka\ParserUnit\DataFixtures;

class ProductItemsTestData
{
    public static $items = array(
        array(
            'name'  => 'BINATONE HC 430',
            'model' => 'HC 430',
            'price' => '279',
            'link'  => 'http://bt.rozetka.com.ua/binatone-hc-430/p246443/'
        ),
        array(
            'name'  => 'Машинка для стрижки BINATONE HC 403',
            'model' => 'HC 403',
            'price' => '181',
            'link'  => 'http://bt.rozetka.com.ua/binatone-h-403/p123309/'
        )
    );

    static public function getHtmlData()
    {
        return file_get_contents(__DIR__.'/product-items.html');
    }
}
