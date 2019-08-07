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


namespace Xfind\Core\Database\SolrEloquent\Concerns;

trait DefaultValueAttributes
{
    protected function getDefaultValue(string $attribute, $value)
    {
        if (array_key_exists($attribute, $this->fillable) && (is_null($value) || empty($value))) {
            foreach ($this->fillable as $key => $defValue) {
                if (is_array($defValue) && array_key_exists('default', $defValue)) {
                    $value = $defValue['default'];
                    break;
                }
            }
        }

        return $value;
    }
}