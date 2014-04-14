<?php

namespace Application\CatalogParser\Parser;

interface CatalogParserInterface
{
    public function setCatalogPageIterator(CatalogPageIteratorInterface $pageIterator);
    public function setParserChainFactory(ParserChainFactoryInterface $chainFactory);
    public function setMaxItemsCount($cnt);
    public function parse();
    public function getItems();
}
