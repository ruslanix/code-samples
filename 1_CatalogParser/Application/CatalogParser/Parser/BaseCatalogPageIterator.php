<?php

namespace Application\CatalogParser\Parser;

use Buzz\Browser;
use Buzz\Client\Curl;

abstract class BaseCatalogPageIterator implements CatalogPageIteratorInterface
{
    protected
        $browser = null,
        $delay = 1
    ;

    public function setDelay($delay)
    {
        $this->delay = $delay;
    }

    protected function getBrowser()
    {
        if(!$this->browser){
            $this->browser = new Browser(new Curl());
        }

        return $this->browser;
    }

    protected function delay()
    {
        sleep($this->delay);
    }
}
