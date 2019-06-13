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

trait GuardsAttributes
{
    use \Illuminate\Database\Eloquent\Concerns\GuardsAttributes;
    use DefaultValueAttributes;

    /**
     * Get the fillable attributes for the model.
     *
     * @return array
     */
    public function getFillable()
    {
        $fillable = [];

        foreach ($this->fillable as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $fillable[] = $value;
                continue;
            }
            $fillable[] = $key;
        }

        return $fillable;
    }

    /**
     * Fill null empty or unset attributes with default values
     *
     * @return array
     */
    public function appendWithDefault(array $attributes)
    {
        foreach ($this->fillable as $key => $value) {
            $defValue = null;
            if (!is_array($value)) {
                continue;
            } elseif (array_key_exists($key, $attributes)) {
                $defValue = $attributes[$key];
            }
            $attributes[$key] = $this->getDefaultValue($key, $defValue);
        }
        return $attributes;
    }

    /**
     * Get the fillable attributes of a given array.
     *
     * @param  array  $attributes
     * @return array
     */
    protected function fillableFromArray(array $attributes)
    {
        if (count($this->getFillable()) > 0 && !static::$unguarded) {
            return array_intersect_key($this->appendWithDefault($attributes), array_flip($this->getFillable()));
        }

        return $attributes;
    }
}