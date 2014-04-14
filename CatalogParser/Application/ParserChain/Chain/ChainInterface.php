<?php

namespace Application\ParserChain\Chain;

use Application\ParserChain\ParserUnit\ParserUnitInterface;

interface ChainInterface
{
    public function addParserUnit(ParserUnitInterface $parserUnit);
    public function getParserUnits();
    public function getResultContainer();
    public function parse($text);
}
