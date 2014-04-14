<?php

namespace Application\CatalogParser\Tests\Parser\Rozetka\ParserUnit;

use Application\ParserChain\Container\ResultContainer;
use Application\CatalogParser\Parser\Rozetka\ParserUnit\ProductLink;
use Application\CatalogParser\Tests\Parser\Rozetka\ParserUnit\DataFixtures\ProductItemTestData;

class ProductLinkTest extends \PHPUnit_Framework_TestCase
{
    public function providerTestParse_validLink()
    {
        return array(
            array(
                ProductItemTestData::getHtmlData(),
                ProductItemTestData::$item['link']
            ),
        );
    }

    public function providerTestParse_notValidLink()
    {
        return array(
            array(null),
            array(''),
            array('<div class="title">some title'),
        );
    }

    /**
     * @dataProvider providerTestParse_validLink
     */
    public function testParse_validLink($html, $link)
    {
        // given
        $resultContainer = new ResultContainer();
        $parserUnit = new ProductLink();

        // when
        $parserUnit->parse($html, $resultContainer);

        // then
        $this->assertArrayHasKey('link', $resultContainer->toArray(), 'result container should have link');
        $this->assertEquals($link, $resultContainer->get('link'), "link should be $link,  but get {$resultContainer->get('link')}");
    }

    /**
     * @dataProvider providerTestParse_notValidLink
     */
    public function testParse_notValidLink($html)
    {
        // given
        $resultContainer = new ResultContainer();
        $parserUnit = new ProductLink();

        // when
        $parserUnit->parse($html, $resultContainer);

        // then
        $this->assertArrayHasKey('link', $resultContainer->toArray(), 'result container should have link');
        $this->assertEquals('', $resultContainer->get('link'), "link should be empty,  but get {$resultContainer->get('link')}");
    }
}
