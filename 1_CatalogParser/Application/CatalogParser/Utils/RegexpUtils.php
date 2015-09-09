<?php

namespace Application\CatalogParser\Utils;

class RegexpUtils
{
    static public function matchSingle($pattern, $subject)
    {
        $matches = array();
        $res = preg_match($pattern, $subject, $matches);

        if($res != 1)
            return null;

        return $matches[1];
    }

    static public function matchAll($pattern, $subject)
    {
        $matches = array();
        $res = preg_match_all($pattern, $subject, $matches);

        if($res === false)
            return null;

        return $matches[1];
    }
}
