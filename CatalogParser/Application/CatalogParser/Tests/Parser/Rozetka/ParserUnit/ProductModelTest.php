<?php

namespace Application\CatalogParser\Tests\Parser\Rozetka\ParserUnit;

use Application\ParserChain\Container\ResultContainer;
use Application\CatalogParser\Parser\Rozetka\ParserUnit\ProductModel;
use Application\CatalogParser\Tests\Parser\Rozetka\ParserUnit\DataFixtures\ProductItemTestData;

class ProductModelTest extends \PHPUnit_Framework_TestCase
{
    public function providerTestParse_validModel()
    {
        return array(
            array(
                ProductItemTestData::getHtmlData(),
                ProductItemTestData::$item['model']
            ),
        );
    }

    public function providerTestParse_notValidModel()
    {
        return array(
            array(null),
            array(''),
            array('<div 234<span>'),
        );
    }

    /**
     * @dataProvider providerTestParse_validModel
     */
    public function testParse_validModel($html, $model)
    {
        // given
        $resultContainer = new ResultContainer();
        $parserUnit = new ProductModel();

        // when
        $parserUnit->parse($html, $resultContainer);

        // then
        $this->assertArrayHasKey('model', $resultContainer->toArray(), 'result container should have model');
        $this->assertEquals($model, $resultContainer->get('model'), "model should be $model,  but get {$resultContainer->get('model')}");
    }

    /**
     * @dataProvider providerTestParse_notValidModel
     */
    public function testParse_notValidModel($html)
    {
        // given
        $resultContainer = new ResultContainer();
        $parserUnit = new ProductModel();

        // when
        $parserUnit->parse($html, $resultContainer);

        // then
        $this->assertArrayHasKey('model', $resultContainer->toArray(), 'result container should have model');
        $this->assertEquals('', $resultContainer->get('model'), "model should be empty,  but get {$resultContainer->get('model')}");
    }
}
