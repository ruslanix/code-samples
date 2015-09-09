<?php

namespace Application\CatalogParser\Parser\Rozetka\ParserUnit;

use Application\ParserChain\Container\ResultContainer;
use Application\ParserChain\ParserUnit\BaseParserUnit;
use Application\CatalogParser\Utils\RegexpUtils;

class ProductName extends BaseParserUnit
{
    public function parse($text, ResultContainer $resultContainer)
    {
        $regexp  = '~<div class="title">.*?<a.*?>(.+?)</a>~si';

        $name = trim(RegexpUtils::matchSingle($regexp, $text));

        $resultContainer->set('name', $name);
    }
}
