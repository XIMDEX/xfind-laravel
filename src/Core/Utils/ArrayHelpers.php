<?php

namespace Xfind\Core\Utils;

class ArrayHelpers
{
    public static function getProperty(array $data, string $property, $default = '', $type = 'string')
    {
        return (isset($data[$property]) && ($value = $data[$property]) && gettype($value) === $type) ? $value : $default;
    }
}
