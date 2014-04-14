<?php

namespace Application\CatalogParser\Parser\Rozetka\ParserUnit;

use Application\ParserChain\Container\ResultContainer;
use Application\ParserChain\ParserUnit\BaseParserUnit;
use Application\CatalogParser\Utils\RegexpUtils;

class ProductLink extends BaseParserUnit
{
    public function parse($text, ResultContainer $resultContainer)
    {
        $regexp  = '~<div class="title">.*?<a.*?href="(.+?)"~si';

        $link = trim(RegexpUtils::matchSingle($regexp, $text));

        $resultContainer->set('link', $link);
    }
}
