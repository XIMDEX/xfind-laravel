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

namespace Xfind\Core\Database\SolrEloquent\Query;

use Xfind\Core\Solr;
use Whoops\Exception\ErrorException;
use Xfind\Core\Database\SolrEloquent\Query\Paginator\Paginator;

class Builder
{

    const WHERE_AND = 'AND';
    const WHERE_OR = 'OR';
    const WHERE_DEFAULT = 'AND';

    const OPERATORS = [
        'AND' => '&& %s',
        'OR' => '|| %s',
        'NOT' => '* !%s',
        '-' => '* -%s',
        '+' => '+%s'
    ];

    protected $order = [];
    protected $filters = [];
    protected $query = '*:*';

    protected $connection;

    public function __construct(Solr $connection)
    {
        $this->setConnection($connection);
    }

    public function setConnection(Solr $connection)
    {
        $this->connection = $connection;
        return $this;
    }

    public function getConnection(): Solr
    {
        return $this->connection;
    }

    public function where(...$params)
    {
        $query = $this->whereParams(...$params);

        $this->setQuery($query, static::WHERE_AND);
        return $this;
    }

    public function orWhere(...$params)
    {
        $query = $this->whereParams(...$params);

        $this->setQuery($query, static::WHERE_OR);
        return $this;
    }

    public function orderBy(string $field, string $value)
    {
        $this->order[$field] = $value;
        return $this;
    }

    public function first()
    {
        $data = $this->get();
        $result = null;

        if (count($data) > 0 && isset($data['docs']) && count($data['docs']) > 0) {
            $result = $data['docs'][0];
        }

        return $result;
    }

    public function get()
    {
        return $this->prepareQuery()->obtain();
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param  int  $perPage
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 20, $pageName = 'page', $page = null)
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);
        $results = $this->prepareQuery()->limit($perPage, $page - 1)->obtain();

        $total = $this->getCountForPagination($results);

        $options = array_only($results, ['facets', 'highlighting']);

        return  Paginator::paginator($results['docs'], $total, $perPage, $page, $options);
    }

    /**
     * Get the count of the total records for the paginator.
     *
     * @param  array  $columns
     * @return int
     */
    public function getCountForPagination($data)
    {
        return $data['numFound'] ?? 0;
    }


    public function getQuery()
    {
        return $this->query;
    }

    protected function prepareQuery()
    {
        return $this->getConnection()
            ->selectQuery($this->query, $this->filters)
            ->sort($this->order);
    }

    protected function isDefaultQuery()
    {
        return $this->query === '*:*';
    }

    protected function setQuery(string $query, string $appendType)
    {
        if ($this->isDefaultQuery()) {
            $this->query = $query;
        } else {
            if (!in_array($appendType, [static::WHERE_AND, static::WHERE_OR])) {
                $appendType = static::WHERE_DEFAULT;
            }
            $append = static::OPERATORS[$appendType];
            $this->query .= sprintf(" {$append}", $query);
        }
    }

    protected function whereParams(...$params)
    {
        $type = count($params);
        $operator = '%s';
        $field = null;
        $value = null;

        if ($type === 1 && is_callable($params[0])) {
            $tmpBuilder = new static($this->connection);
            $params[0]($tmpBuilder);
            $query = $tmpBuilder->getQuery();
            return "({$query})";
        } elseif ($type === 2) {
            $field = trim($params[0]);
            $value = trim($params[1]);
        } elseif ($type === 3) {
            $field = trim($params[0]);
            $operator = array_key_exists($params[1], static::OPERATORS) ? static::OPERATORS[$params[1]] : $operator;
            $value = trim($params[2]);
        } else {
            throw new ErrorException("The where operator must need almost 2 parameters and {$type} given");
        }

        return sprintf($operator, "{$field}:$value");
    }
}
