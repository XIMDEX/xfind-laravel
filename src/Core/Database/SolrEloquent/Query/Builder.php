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
use Illuminate\Support\Arr;
use Whoops\Exception\ErrorException;
use Xfind\Core\Database\SolrEloquent\Model;
use Illuminate\Support\Traits\ForwardsCalls;
use Xfind\Core\Database\SolrEloquent\FacetSettings;
use Xfind\Core\Database\SolrEloquent\Query\Paginator\Paginator;

class Builder
{
    use ForwardsCalls;

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
    protected $facets = [];
    protected $query = '*:*';

    protected $model;
    protected $connection;

    public function __construct(Solr $connection, Model $model)
    {
        $this->setConnection($connection)
            ->setModel($model);
    }

    public function setModel(Model $model)
    {
        $this->model = $model;
        return $this;
    }

    public function getModel()
    {
        return $this->model;
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

    /**
     * Add a where clause on the primary key to the query.
     *
     * @param  mixed  $id
     * @return $this
     */
    public function whereKey($id)
    {
        return $this->where($this->model->getKeyName(), $id);
    }

    public function orderBy(string $field, string $value)
    {
        $this->order[$field] = $value;
        return $this;
    }

    public function addFacet($facet, $field = null, $settings = null)
    {
        if (is_array($field) || $field instanceof FacetSettings) {
            $settings = $field;
            $field = null;
        }

        if (!is_array($settings) && !is_null($settings) && !$settings instanceof FacetSettings) {
            throw new InvalidArgumentException('The argument $settings must be one of these types (array, null, FacetSettings) and given type is ' . gettype($settings));
        }

        $field = $field ?? $facet;
        $this->connection->facetField($facet, $field, $settings);
        $this->facets[$field] = $settings;
        return $this;
    }

    /**
     * Enable all facets to current select query
     *
     * @param array|null|\Xfind\Core\Database\SolrEloquent\FacetSettings $settings
     * @return \Xfind\Core\Database\SolrEloquent\Builder
     */
    public function withFacets($settings = null)
    {
        if (!is_array($settings) && !is_null($settings) && !$settings instanceof FacetSettings) {
            throw new InvalidArgumentException('The argument $settings must be one of these types (array, null, FacetSettings) and given type is ' . gettype($settings));
        }

        $facets = $this->getModel()->getFacets();

        foreach ($facets as $facet) {
            $this->addFacet($facet, $settings);
        }

        return $this;
    }

    /**
     * Save a new model and return the instance.
     *
     * @param  array  $attributes
     * @return Xfind\Core\Database\SolrEloquent\Model|static
     */
    public function create(array $attributes)
    {
        return tap($this->newModelInstance($attributes), function ($model) {
            $model->save();
        });
    }

    /**
     * Update a record in the database.
     *
     * @param  array  $values
     * @return int
     */
    public function update(array $values)
    {
        $data = $this->firstOrFail();
        return tap($data, function ($model) use ($values) {
            $model->fill($values)->save();
        });
    }

    /**
     * Create or update a record matching the attributes, and fill it with values.
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return Xfind\Core\Database\SolrEloquent\Model|static
     */
    public function updateOrCreate(array $attributes, array $values = [])
    {
        $data = $this->firstOrNew($attributes, $values);
        return tap($data, function ($model) use ($values) {
            $model->fill($values)->save();
        });
    }

    /**
     * Get the first record matching the attributes or instantiate it.
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return Xfind\Core\Database\SolrEloquent\Model|static
     */
    public function firstOrNew(array $attributes, array $values = [])
    {
        foreach ($attributes as $key => $value) {
            $this->where($key, "\"{$value}\"");
        }
        if (!is_null($instance = $this->first())) {
            return $instance;
        }

        return $this->newModelInstance($attributes + $values);
    }

    /**
     * Get the first record matching the attributes or create it.
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function firstOrCreate(array $attributes, array $values = [])
    {
        foreach ($attributes as $key => $value) {
            $this->where($key, "\"{$value}\"");
        }
        if (!is_null($instance = $this->first())) {
            return $instance;
        }

        return tap($this->newModelInstance($attributes + $values), function ($instance) {
            $instance->save();
        });
    }

    /**
     * Get the first record matching the attributes or fail.
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return Xfind\Core\Database\SolrEloquent\Model|static
     */
    public function firstOrFail()
    {
        $data = $this->first();
        if (is_null($data)) {
            throw (new ModelNotFoundException)->setModel(
                get_class($this->model),
                $this->query
            );
        }

        return $data;
    }

    /**
     * Find a model by its primary key.
     *
     * @param  mixed  $id
     * @return \Illuminate\Database\Eloquent\Model|static[]|static|null
     */
    public function find($id)
    {
        return $this->whereKey($id)->first();
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param  mixed  $id
     * @return \Illuminate\Database\Eloquent\Mode|static|static[]
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id)
    {
        $result = $this->find($id);

        if (is_array($id)) {
            if (count($result) === count(array_unique($id))) {
                return $result;
            }
        } elseif (!is_null($result)) {
            return $result;
        }

        throw (new ModelNotFoundException)->setModel(
            get_class($this->model),
            $id
        );
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
        $data = $this->prepareQuery()->obtain();
        return $this->prepareResult($data);
    }

    /**
     * Insert a new record into the solr.
     *
     * @param  array  $values
     * @return bool
     */
    public function insert(array $values): bool
    {
        if (empty($values)) {
            return true;
        }

        $query = $this->getConnection()->createUpdate();
        $doc = $query->createDocument();

        foreach ($values as $field => $value) {
            $doc->$field = $value;
        }

        $query->addDocument($doc);
        $query->addCommit();
        $result = $this->getConnection()->update($query);

        return $result->getResponse()->getStatusCode() == 200;
    }

    /**
     * Create a new instance of the model being queried.
     *
     * @param  array  $attributes
     * @param boolean $exists
     * @return Xfind\Core\Database\SolrEloquent\Model|static
     */
    public function newModelInstance($attributes = [], bool $exists = false)
    {
        return $this->model->newInstance($attributes, $exists);
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

        $results = $this->prepareResult($results);
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

    protected function prepareResult($data)
    {
        $items = Arr::get($data, 'docs', []);
        $facets = Arr::get($data, 'facets', null);

        $instance = $this->newModelInstance();
        $items = $instance->newCollection(array_map(function ($item) use ($instance) {
            return $instance->newFromBuilder($item);
        }, $items));

        if (!is_null($facets)) {
            $data['facets'] = $this->prepareFacets($facets);
        }
        $data['docs'] = $items;
        return $data;
    }

    protected function prepareFacets($data)
    {
        foreach ($data as &$facet) {
            $key = $facet['key'];
            if (array_key_exists($key, $this->facets) && array_key_exists('default', $this->facets[$key])) {
                $defaultFacet = $this->facets[$key]['default'];
                $facet['default'] = $defaultFacet;
            }
        }
        return $data;
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
            $tmpBuilder = new static($this->connection, $this->getModel());
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

        return sprintf($operator, "{$field}:{$value}");
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->getModel(), $method, $parameters);
    }
}
