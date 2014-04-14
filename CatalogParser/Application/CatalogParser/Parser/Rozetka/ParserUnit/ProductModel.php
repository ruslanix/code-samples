<?php

namespace Application\CatalogParser\Parser\Rozetka\ParserUnit;

use Application\ParserChain\Container\ResultContainer;
use Application\ParserChain\ParserUnit\BaseParserUnit;
use Application\CatalogParser\Utils\RegexpUtils;

class ProductModel extends BaseParserUnit
{
    public function parse($text, ResultContainer $resultContainer)
    {
        $regexp  = '~<div class="title">.*?<a.*?>(.+?)</a>~si';

        $model = trim(RegexpUtils::matchSingle($regexp, $text));
        $model = $this->cutAdditionalInfo($model);
        $model = $this->leaveOnlyLatinSymbolsAndSpecialChars($model);
        $model = $this->cutCompanyName($model);

        $resultContainer->set('model', $model);
    }

    protected function cutAdditionalInfo($model)
    {
        return trim(preg_replace('~ \+.*$~si','', $model));
    }

    protected function leaveOnlyLatinSymbolsAndSpecialChars($model)
    {
        return trim(preg_replace('~[^a-z0-9_\- ]~i','', $model));
    }

    protected function cutCompanyName($model)
    {
        return trim(preg_replace('~^.+? ~i','', $model));
    }
}
