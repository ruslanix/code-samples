<?php

namespace Application\CatalogParser\Parser;

use Doctrine\Common\Collections\ArrayCollection;

abstract class BaseParserChainFactory implements ParserChainFactoryInterface
{
    protected
        $chains
    ;

    public function __construct()
    {
        $this->chains = new ArrayCollection();
    }

    public function getChain($chainName)
    {
        if(!$this->chains->containsKey($chainName)){

            $buildChainMethod = 'buildChain' . $chainName;

            if(!method_exists($this, $buildChainMethod)){
                throw new \Exception("[BaseParserChainFactory] You should define '$buildChainMethod' method");
            }

            $this->chains->set($chainName, call_user_func_array(array($this, $buildChainMethod), array()));
        }

        return $this->chains->get($chainName);
    }
}
