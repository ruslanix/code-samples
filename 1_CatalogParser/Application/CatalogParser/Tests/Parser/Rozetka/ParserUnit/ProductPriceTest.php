<?php

namespace Application\CatalogParser\Tests\Parser\Rozetka\ParserUnit;

use Application\ParserChain\Container\ResultContainer;
use Application\CatalogParser\Parser\Rozetka\ParserUnit\ProductPrice;
use Application\CatalogParser\Tests\Parser\Rozetka\ParserUnit\DataFixtures\ProductItemTestData;

class ProductPriceTest extends \PHPUnit_Framework_TestCase
{
    public function providerTestParse_validPrice()
    {
        return array(
            array(
                ProductItemTestData::getHtmlData(),
                ProductItemTestData::$item['price']
            ),
        );
    }

    public function providerTestParse_notValidPrice()
    {
        return array(
            array(null),
            array(''),
            array('<div 234<span>'),
        );
    }

    /**
     * @dataProvider providerTestParse_validPrice
     */
    public function testParse_validPrice($html, $price)
    {
        // given
        $resultContainer = new ResultContainer();
        $parserUnit = new ProductPrice();

        // when
        $parserUnit->parse($html, $resultContainer);

        // then
        $this->assertArrayHasKey('price', $resultContainer->toArray(), 'result container should have price');
        $this->assertEquals($price, $resultContainer->get('price'), "price should be $price,  but get {$resultContainer->get('price')}");
    }

    /**
     * @dataProvider providerTestParse_notValidPrice
     */
    public function testParse_notValidPrice($html)
    {
        // given
        $resultContainer = new ResultContainer();
        $parserUnit = new ProductPrice();

        // when
        $parserUnit->parse($html, $resultContainer);

        // then
        $this->assertArrayHasKey('price', $resultContainer->toArray(), 'result container should have price');
        $this->assertEquals('', $resultContainer->get('price'), "price should be empty,  but get {$resultContainer->get('price')}");
    }
}
