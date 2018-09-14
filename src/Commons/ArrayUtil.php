<?php

namespace Sta\Commons;

class ArrayUtil
{
    /**
     * @param $arr
     *
     * @return bool
     */
    public static function isAssocArray($arr)
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
