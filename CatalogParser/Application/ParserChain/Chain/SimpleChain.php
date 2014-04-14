<?php

namespace Application\ParserChain\Chain;

class SimpleChain extends BaseChain
{
    protected function doParse($text)
    {
        foreach($this->getParserUnits() as $parserUnit){
            $parserUnit->parse($text, $this->getResultContainer());
        }
    }
}
