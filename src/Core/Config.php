<?php

namespace Xfind\Core;

class Config
{
    private const CONFIG_FILE = '/.config';

    private static function open(bool $groups = false)
    {
        $file = dirname(dirname(__FILE__)) . self::CONFIG_FILE;
        $result = parse_ini_file($file, $groups);
        return $result;
    }

    public static function get(string $config = '')
    {
        $configs = self::open();

        if (!empty($config) && array_key_exists($config, $configs)) {
            $configs = $configs[$config];
        }

        return $configs;
    }

    public static function getGroup(string $group = '')
    {
        $configs = self::open(true);

        if (!empty($group) && array_key_exists($group, $configs)) {
            $configs = $configs[$group];
        }

        return $configs;
    }
}
