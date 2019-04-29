<?php
/**
 * Copyright (C) 2019 Open Ximdex Evolution SL [http://www.ximdex.org]
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/agpl-3.0.html>.
 */


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
