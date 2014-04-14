<?php

namespace Application\ParserChain\Chain;

use Doctrine\Common\Collections\ArrayCollection;

use Application\ParserChain\Container\ResultContainer;
use Application\ParserChain\ParserUnit\ParserUnitInterface;

abstract class BaseChain implements ChainInterface
{
    protected
        $parserUnits,
        $resultContainer
    ;

    public function __construct()
    {
        $this->parserUnits = new ArrayCollection();
        $this->resultContainer = new ResultContainer();
    }

    abstract protected function doParse($text);

    public function addParserUnit(ParserUnitInterface $parserUnit)
    {
        $this->parserUnits->add($parserUnit);
    }

    public function getParserUnits()
    {
        return $this->parserUnits;
    }

    public function getResultContainer()
    {
        return $this->resultContainer;
    }

    public function parse($text)
    {
        $this->resultContainer->clear();

        $this->doParse($text);

        return $this->getResultContainer();
    }
}
