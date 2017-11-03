<?php

namespace App\RestApiComponent\Request\ParametersFetcher;

use App\RestApiComponent\Request\Parameter\AbstractParameterDefinition;
use App\RestApiComponent\Request\AbstractParametersFetcher;

/**
 * This is wrapper above ParametersFetcher that allow chaining configuration for parameters definition.
 * Example:
 *
 *      $fetcher
 *          ->configure()                           // get instance of Configurator class
 *              ->filter()                          // select filter definition to configure
 *                  ->setDefaultValue(..)           // configure filter
 *                  ->setAllowedFilters()  // configure filter
 *              ->sort()                            // select sort definition to configure
 *                  ->allow()                       // configure sort
 *                  ->setDefaultValue(..)           // configure sort
 *          ->fetch($request)                       // stop configuration chain and call method from fetcher
 */
class Configurator
{
    /**
     *
     * @var AbstractParametersFetcher
     */
    protected $fetcher;

    /**
     *
     * @var AbstractParameterDefinition
     */
    protected $currentParameterDefinition;

    /**
     *
     * @param AbstractParametersFetcher $fetcher
     */
    public function __construct(AbstractParametersFetcher $fetcher)
    {
        $this->fetcher = $fetcher;
    }

    public function __call($name, $arguments)
    {
        // if method exists in fetcher - return it result
        if (method_exists($this->fetcher, $name)) {
            return call_user_func_array(array($this->fetcher, $name), $arguments);
        }

        // if method name equals to one of the parameter definition - select that definition as current context
        // for method calls
        // equivalent to:
        //      $fetcher
        //          ->configure()
        //              ->filter()  - here we select filter as definition for next function calls
        //
        if ($defintion = $this->getParameterDefinition($name)) {
            $this->currentParameterDefinition = $defintion;
            return $this;
        }

        // if current definition is selected and method exists in definition - call it
        // but return $this because we need chainig
        // equivalent to:
        //      $fetcher
        //          ->configure()
        //              ->filter()
        //                  ->setDefaultValue(...) - here we set default value for `filter` definition
        //
        if ($this->currentParameterDefinition && method_exists($this->currentParameterDefinition, $name)) {
            call_user_func_array(array($this->currentParameterDefinition, $name), $arguments);
            return $this;
        }

        // something wrong here
        if ($this->currentParameterDefinition) {
            throw new \LogicException(sprintf(
                "[ParametersFetcher::Configurator] Parameter definition (%s) doesn't have method `%s`."
                . " Same as fetcher (%s) doesn't have method or parameter definition with such name.",
                get_class($this->currentParameterDefinition),
                $name,
                get_class($this->fetcher)
            ));
        } else {
            throw new \LogicException(sprintf(
                "[ParametersFetcher::Configurator] Fetcher (%s) doesn't have method "
                . "or parameter definition with name `%s`",
                get_class($this->fetcher),
                $name
            ));
        }
    }

    /**
     *
     * @param string $name
     * @return AbstractParameterDefinition|null
     */
    protected function getParameterDefinition($name)
    {
        $method  = new \ReflectionMethod(get_class($this->fetcher), 'getDefinition');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->fetcher, [$name]);
        $method->setAccessible(false);
        return $result;
    }
}
