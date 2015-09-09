<?php

namespace Application\CatalogParser\Parser\Rozetka\ParserUnit;

use Application\ParserChain\Container\ResultContainer;
use Application\ParserChain\ParserUnit\BaseParserUnit;
use Application\CatalogParser\Utils\RegexpUtils;

class ProductPrice extends BaseParserUnit
{
    public function parse($text, ResultContainer $resultContainer)
    {
        $regexp  = '~<div class="uah">(.+?)<span>~si';

        $price = trim(RegexpUtils::matchSingle($regexp, $text));

        $resultContainer->set('price', $price);
    }
}
