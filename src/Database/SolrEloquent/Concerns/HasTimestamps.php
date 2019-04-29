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


namespace Xfind\Database\SolrEloquent\Concerns;

trait HasTimestamps
{
    use \Illuminate\Database\Eloquent\Concerns\HasTimestamps;

    /**
     * Get the name of the "indexed at" column.
     *
     * @return string
     */
    public function getIndexedAtColumn()
    {
        return static::INDEXED_AT;
    }

    /**
     * Set the value of the "indexed at" attribute.
     *
     * @param  mixed  $value
     * @return $this
     */
    public function setIndexedAt($value)
    {
        $this->{static::INDEXED_AT} = $value;
        return $this;
    }

    /**
     * Update the creation and update timestamps.
     *
     * @return void
     */
    protected function updateTimestamps()
    {
        $time = $this->freshTimestamp();
        if (!is_null(static::INDEXED_AT)) {
            $this->setIndexedAt($time);
        }
    }
}
