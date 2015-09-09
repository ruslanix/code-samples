<?php

namespace Application\CatalogParser\Tests\Parser\Rozetka\ParserUnit;

use Application\ParserChain\Container\ResultContainer;
use Application\CatalogParser\Parser\Rozetka\ParserUnit\PageProductItems;
use Application\CatalogParser\Tests\Parser\Rozetka\ParserUnit\DataFixtures\ProductItemsTestData;

class PageProductItemsTest extends \PHPUnit_Framework_TestCase
{
    public function providerTestParse_validItems()
    {
        return array(
            array(
                ProductItemsTestData::getHtmlData(),
                count(ProductItemsTestData::$items)
            ),
        );
    }

    public function providerTestParse_notValidItems()
    {
        return array(
            array(null),
            array(''),
            array('<div 234<span>'),
        );
    }

    /**
     * @dataProvider providerTestParse_validItems
     */
    public function testParse_validItems($html, $itemsCount)
    {
        // given
        $resultContainer = new ResultContainer();
        $parserUnit = new PageProductItems();

        // when
        $parserUnit->parse($html, $resultContainer);

        // then
        $this->assertArrayHasKey('items', $resultContainer->toArray(), 'result container should have items');
        $this->assertCount($itemsCount, $resultContainer->get('items'), "should be $itemsCount in items");
    }

    /**
     * @dataProvider providerTestParse_notValidItems
     */
    public function testParse_notValidItems($html)
    {
        // given
        $resultContainer = new ResultContainer();
        $parserUnit = new PageProductItems();

        // when
        $parserUnit->parse($html, $resultContainer);

        // then
        $this->assertArrayHasKey('items', $resultContainer->toArray(), 'result container should have items');
        $this->assertEmpty($resultContainer->get('items'), "items should be empty");
    }
}
