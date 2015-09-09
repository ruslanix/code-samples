<?php

namespace Mesh\EdmMailChimpBundle\Service\Api\LowLevel;

class MailChimpResponse
{
    protected $errorMessage = null;
    protected $content = null;
    protected $validationErrors = array();
    
    public function setErrorMessage($errorMessage)
    {   
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    public function hasError()
    {
        return !empty($this->errorMessage);
    }
    
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function hasContent()
    {
        return !empty($this->content);
    }

    public function addValidationError($error)
    {
        $this->validationErrors[] = $error;

        return $this;
    }

    public function getValidationErrors()
    {
        return $this->validationErrors;
    }

    public function hasValidationErrors()
    {
        return count($this->validationErrors) > 0;
    }

    public function isOk()
    {
        return $this->hasContent() && !$this->hasError() && !$this->hasValidationErrors();
    }
}