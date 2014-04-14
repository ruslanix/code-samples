<?php

namespace Application\CatalogParser\Parser\Rozetka;

use Application\ParserChain\Chain\SimpleChain;

use Application\CatalogParser\Parser\BaseParserChainFactory;
use Application\CatalogParser\Parser\Rozetka\ParserUnit;


class RozetkaParserChainFactory extends BaseParserChainFactory
{
    public function buildChainPage()
    {
        $chain = new SimpleChain();
        $chain->addParserUnit(new ParserUnit\PageProductItems());

        return $chain;
    }

    protected function buildChainProduct()
    {
        $chain = new SimpleChain();
        $chain->addParserUnit(new ParserUnit\ProductName());
        $chain->addParserUnit(new ParserUnit\ProductModel());
        $chain->addParserUnit(new ParserUnit\ProductPrice());
        $chain->addParserUnit(new ParserUnit\ProductLink());        

        return $chain;
    }
}
