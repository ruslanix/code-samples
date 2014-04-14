<?php

namespace Application\CatalogParser\Tests\Parser\Rozetka\ParserUnit;

use Application\ParserChain\Container\ResultContainer;
use Application\CatalogParser\Parser\Rozetka\ParserUnit\ProductName;
use Application\CatalogParser\Tests\Parser\Rozetka\ParserUnit\DataFixtures\ProductItemTestData;

class ProductNameTest extends \PHPUnit_Framework_TestCase
{
    public function providerTestParse_validName()
    {
        return array(
            array(
                ProductItemTestData::getHtmlData(),
                ProductItemTestData::$item['name']
            ),
        );
    }

    public function providerTestParse_notValidName()
    {
        return array(
            array(null),
            array(''),
            array('<div class="title">some title'),
        );
    }

    /**
     * @dataProvider providerTestParse_validName
     */
    public function testParse_validName($html, $name)
    {
        // given
        $resultContainer = new ResultContainer();
        $parserUnit = new ProductName();

        // when
        $parserUnit->parse($html, $resultContainer);

        // then
        $this->assertArrayHasKey('name', $resultContainer->toArray(), 'result container should have name');
        $this->assertEquals($name, $resultContainer->get('name'), "name should be $name,  but get {$resultContainer->get('name')}");
    }

    /**
     * @dataProvider providerTestParse_notValidName
     */
    public function testParse_notValidName($html)
    {
        // given
        $resultContainer = new ResultContainer();
        $parserUnit = new ProductName();

        // when
        $parserUnit->parse($html, $resultContainer);

        // then
        $this->assertArrayHasKey('name', $resultContainer->toArray(), 'result container should have name');
        $this->assertEquals('', $resultContainer->get('name'), "name should be empty,  but get {$resultContainer->get('name')}");
    }
}
