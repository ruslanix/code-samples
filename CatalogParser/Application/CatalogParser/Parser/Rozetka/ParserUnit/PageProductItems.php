<?php

namespace Application\CatalogParser\Parser\Rozetka\ParserUnit;

use Application\ParserChain\Container\ResultContainer;
use Application\ParserChain\ParserUnit\BaseParserUnit;
use Application\CatalogParser\Utils\RegexpUtils;

class PageProductItems extends BaseParserUnit
{
    public function parse($text, ResultContainer $resultContainer)
    {
        $regexp  = '~<table class="item(.+?)<div class="to-wishlist">~si';

        $items = RegexpUtils::matchAll($regexp, $text);
        
        $resultContainer->set('items', $items);
    }
}
