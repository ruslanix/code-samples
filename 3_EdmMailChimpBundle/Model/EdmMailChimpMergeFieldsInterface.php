<?php

namespace Mesh\EdmMailChimpBundle\Model;

interface EdmMailChimpMergeFieldsInterface
{
    /**
     * Merge fields tags
     */
    const BIRTHDATE_TAG = 'BIRTHDATE';
    const BIRTHDATE_NAME = 'Birth date';
    const BIRTHDATE_TYPE = 'date';

    CONST GENDER_TAG = 'GENDER';
    CONST GENDER_NAME = 'Gender';
    CONST GENDER_TYPE = 'text';
}