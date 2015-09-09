<?php

namespace Application\CatalogParser\Parser;

interface ParserChainFactoryInterface
{
    public function getChain($chainName);
}
