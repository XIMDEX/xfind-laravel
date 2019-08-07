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

use DateTime;

trait HasAttributes
{
    use \Illuminate\Database\Eloquent\Concerns\HasAttributes;

    /**
     * Get the casts array.
     *
     * @return array
     */
    public function getCasts()
    {
        return $this->casts;
    }

    /**
     * Get a relationship.
     *
     * @return mixed
     */
    public function getRelationValue()
    {
        return null;
    }

    /**
     * Get the attributes that should be converted to dates.
     *
     * @return array
     */
    public function getDates()
    {
        $defaults = [static::CREATED_AT, static::UPDATED_AT, static::INDEXED_AT];
        return $this->usesTimestamps()
            ? array_unique(array_merge($this->dates, $defaults))
            : $this->dates;
    }

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat()
    {
        return $this->dateFormat ?: config('xfind.date.format', DateTime::ISO8601);
    }

    /**
     * Convert a DateTime to a storable string.
     *
     * @param  \DateTime|int  $value
     * @return string
     */
    public function fromDateTime($value)
    {
        if (!empty($value)) {
            if (is_array($value)) {
                $value = reset($value);
            }
            $value = $this->asDateTime($value)->format(
                $this->getDateFormat()
            );
        }

        return $value;
    }
}