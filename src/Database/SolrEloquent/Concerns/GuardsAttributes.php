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

trait GuardsAttributes
{
    use \Illuminate\Database\Eloquent\Concerns\GuardsAttributes;
    
    /**
     * Get the fillable attributes for the model.
     *
     * @return array
     */
    public function getFillable()
    {
        return array_merge($this->fillable, [
            $this->getIndexedAtColumn(),
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn()
        ]);
    }
}
