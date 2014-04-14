<?php

namespace Application\CatalogParser\Tests\Parser\Rozetka;

use Application\CatalogParser\Parser\Rozetka\RozetkaParser;
use Application\CatalogParser\Parser\Rozetka\RozetkaParserChainFactory;
use Application\CatalogParser\Tests\Parser\Rozetka\ParserUnit\DataFixtures\ProductItemsTestData;

class RozetkaParserTest extends \PHPUnit_Framework_TestCase
{
    public function testParse()
    {
        // given
        $mockPageIterator = $this->getMockPageIterator();

        $parser = new RozetkaParser();
        $parser->setCatalogPageIterator($mockPageIterator);
        $parser->setParserChainFactory(new RozetkaParserChainFactory());
        $parser->setMaxItemsCount(count(ProductItemsTestData::$items));

        // when
        $parser->parse();

        // then
        $this->assertEquals(ProductItemsTestData::$items, $parser->getItems(), 'Wrong parsed items');
    }

    public function testParse_ShouldStopWhenMaxItemsCountReached()
    {
        // given
        $mockPageIterator = $this->getMockPageIterator();

        $parser = new RozetkaParser();
        $parser->setCatalogPageIterator($mockPageIterator);
        $parser->setParserChainFactory(new RozetkaParserChainFactory());
        $parser->setMaxItemsCount(5);

        // when
        $parser->parse();

        // then
        $this->assertCount(5, $parser->getItems(), 'Wrong items count');
    }

    public function testParse_ShouldStopWhenPageIteratorReturnFalse()
    {
        // given
        $mockPageIterator = $this->getMockBuilder('\Application\CatalogParser\Parser\Rozetka\RozetkaPageIterator')
            ->disableOriginalConstructor()
            ->getMock();
        
        $mockPageIterator->expects($this->any())
            ->method('getNextPage')
            ->will($this->onConsecutiveCalls(
                ProductItemsTestData::getHtmlData(),
                ProductItemsTestData::getHtmlData(),
                false
            ));

        $parser = new RozetkaParser();
        $parser->setCatalogPageIterator($mockPageIterator);
        $parser->setParserChainFactory(new RozetkaParserChainFactory());
        $parser->setMaxItemsCount(100);

        // when
        $parser->parse();

        // then
        $expectedItemsCount = count(ProductItemsTestData::$items) * 2;
        $this->assertCount($expectedItemsCount, $parser->getItems(), 'Wrong items count');
    }

    protected function getMockPageIterator()
    {
        $mockPageIterator = $this->getMockBuilder('\Application\CatalogParser\Parser\Rozetka\RozetkaPageIterator')
            ->disableOriginalConstructor()
            ->getMock();

        $mockPageIterator->expects($this->any())
            ->method('getNextPage')
            ->will($this->returnValue(ProductItemsTestData::getHtmlData()));

        return $mockPageIterator;
    }
}
