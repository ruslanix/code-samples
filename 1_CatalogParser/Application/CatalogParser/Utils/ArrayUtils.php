<?php

namespace Application\CatalogParser\Utils;

class ArrayUtils
{
    static public function sortByKey(&$array, $key, $sortOrder = SORT_ASC)
    {
        return usort($array, function($a, $b) use ($key, $sortOrder) {
            if($sortOrder == SORT_ASC)
                return $a[$key] - $b[$key];
            else
                return $b[$key] - $a[$key];
        });
    }

    static public function array2scv($array, $colSep = ",", $rowSep = "\n", $quote = '"')
    {
        if (!is_array($array) or !isset($array[0]) or !is_array($array[0])){
            return false;
        }

        $res = '';

        foreach ($array as $val)
        {
            $tmp = '';
            foreach ($val as $cellVal)
            {
                $cellVal = str_replace($quote, "$quote$quote", $cellVal);
                $tmp .= "$colSep$quote$cellVal$quote";
            }
            $res .= substr($tmp, 1).$rowSep;
        }

        return $res;
    }
}
