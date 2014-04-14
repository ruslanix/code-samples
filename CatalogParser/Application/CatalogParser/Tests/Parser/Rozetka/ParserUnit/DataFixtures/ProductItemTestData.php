<?php

namespace Application\CatalogParser\Tests\Parser\Rozetka\ParserUnit\DataFixtures;

class ProductItemTestData
{
    public static $item = array(
        'name'  => 'Утюг TEFAL FV 4680',
        'model' => 'FV 4680',
        'price' => 508,
        'link'  => 'http://bt.rozetka.com.ua/tefal-fv-4680/p182010/'
    );

    static public function getHtmlData()
    {
        return file_get_contents(__DIR__.'/product-item.html');
    }
}
