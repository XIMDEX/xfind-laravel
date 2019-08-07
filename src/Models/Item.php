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


namespace Xfind\Models;

use Xfind\Core\Solr;
use Illuminate\Support\Str;
use Solarium\Exception\InvalidArgumentException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

abstract class Item
{
    protected $limitPerPage = 20;
    protected $page = 0;
    protected $start = 0;
    protected $query = '*:*';
    protected $filters = [];
    protected $sort = [];

    protected $defaultSort = ['updated_at' => 'desc'];
        
    public static $search = null;

    protected static $rules = [
        'id' => ['field' => 'id', 'type' => 'string', 'required' => true],
        'created_at' => ['field' => 'datetime', 'type' => 'string', 'required' => false],
        'updated_at' => ['field' => 'datetime', 'type' => 'string', 'required' => false],
    ];


    protected static $facets = [];

    protected $fields = [];

    protected $filterFields = [];

    protected $highlight_fields = [];

    private $client;

    public function __construct($client = null)
    {
        $this->client = $client;
        if ($this->client == null) {
            $this->client = new Solr();
        }
        static::$rules = array_merge(static::$rules, self::$rules);
        $this->fields = array_merge(array_keys(static::$rules), $this->fields);
    }

    /**
     * Get the value of limitPerPage
     */
    public function getLimitPerPage()
    {
        return $this->limitPerPage;
    }

    /**
     * Set the value of limitPerPage
     *
     * @return  self
     */
    public function setLimitPerPage($limitPerPage)
    {
        $this->limitPerPage = $limitPerPage;

        return $this;
    }

    /**
     * Get the value of page
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Set the value of page
     *
     * @return  self
     */
    public function setPage($page)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Get the value of query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Set the value of query
     *
     * @return  self
     */
    public function setQuery($query)
    {
        $query = trim($query);
        if (starts_with($query, 'AND')) {
            $query = trim(str_replace_first('AND', '', $query));
        }
        $this->query = $query;

        return $this;
    }

    /**
     * Append the value of query
     *
     * @return  self
     */
    public function appendQuery($query)
    {
        if ($this->query === '*:*' || $query === '*:*') {
            return $this->setQuery($query);
        }
        
        $this->query .= " {$query}";
        $this->query = trim($this->query);

        if (Str::startsWith($this->query, 'AND')) {
            $this->query = trim(str_replace_first('AND', '', $this->query));
        }

        return $this;
    }

    /**
     * Get the value of facets
     */
    public function getFacets()
    {
        return static::$facets;
    }

    /**
     * Get the value of facets
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Set the value of start
     *
     * @return  self
     */
    public function setStart($start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Get the value of start
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set the value of sort
     *
     * @return  self
     */
    public function setSort(array $sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get the value of sort
     */
    public function getSort(): array
    {
        return $this->sort;
    }
    
    public function addFilter(string $query, string $name = null)
    {
        if (is_null($name)) {
            $name = uniqid();
        }

        $this->filters[$name] = $query;
        return $this;
    }

    public function getFilters() : array
    {
        return $this->filters;
    }

    public function isFilter($field) : bool
    {
        return in_array($field, $this->filterFields);
    }


    /**
     * Remove facets from array
     */
    public function removeFacets(array $facets)
    {
        static::$facets = array_diff(static::$facets, $facets);
    }

    public function find($query = null, array $sort = [])
    {
        if (is_null($query)) {
            $query = $this->query;
        }

        if (count($sort) === 0) {
            $sort = $this->getSort();
        }

        $this->client
            ->selectQuery($query, $this->filters)
            ->sort($sort);
        
        foreach (static::$facets as $facet) {
            try {
                $this->facetField($facet, $facet);
            } catch (InvalidArgumentException $ex) {
                \Log::warning($ex);
            }
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function highlight()
    {
        if (count($this->highlight_fields) > 0) {
            $hl = $this->client->getQuery()->getHighlighting();
            $hl->setFields($this->highlight_fields);
            $hl->setFragSize(160);
            $hl->setSimplePrefix('<b>');
            $hl->setSimplePostfix('</b>');
        }

        return $this;
    }

    public function byId(string $id)
    {
        return $this->one("id:'{$id}'");
    }

    public function one($query)
    {
        $result = $this->find($query)
            ->get();

        if (count($result) > 0 && isset($result['docs']) && count($result['docs']) > 0) {
            return $result['docs'][0];
        }

        return null;
    }

    public function get()
    {
        return $this->client->obtain();
    }

    public function createOrUpdate()
    {
        $update = $this->client->createUpdate();

        $doc = $update->createDocument();

        foreach ($this->fields as $field) {
            if (property_exists($this, $field)) {
                $doc->$field = $this->$field;
            }
        }

        $update->addDocument($doc);
        $update->addCommit();
        $result = $this->client->update($update);
        return $result->getResponse()->getStatusCode() == 200;
    }

    public function delete($id)
    {
        $query = "id:\"{$id}\"";
        $exists = $this->one($query);
        if (is_null($exists)) {
            throw new ModelNotFoundException();
        }
        $delete = $this->client->createUpdate();
        $delete->addDeleteById($id);
        $delete->addCommit();
        $updated = $this->client->update($delete);

        $exists = $this->one($query);
        return is_null($exists);
    }

    public function ping()
    {
        return $this->client->test();
    }

    public function facetField($facet, $field)
    {
        $this->client->facetField($facet, $field);
        return $this;
    }

    public function limit(int $start = null, int $limit = null)
    {
        if (is_null($limit)) {
            $limit = $this->limitPerPage;
        }

        if (is_null($start)) {
            $start = $this->start;
        }

        $this->client->limit($limit, $start);
        return $this;
    }

    public function select(array $params)
    {
        $this->client->fields($params);
        return $this;
    }

    public function paginate($page = null)
    {
        if (is_null($page)) {
            $page = $this->page;
        }
        if ($page > 0) {
            $page -= 1;
        }


        $result = $this->client->limit($this->limitPerPage, $this->start + ($this->limitPerPage * $page))->obtain();

        $total = $result['numFound'];
        $pages = ceil($total / $this->limitPerPage);
        $page = $page + 1;
        $next = $page + 1;
        $prev = $page - 1;
        $pager = [
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
            'next' => ($next >= $pages) ? $pages : $next,
            'prev' => ($prev <= 0) ? 1 : $prev,
            'per_page' => $this->limitPerPage,
        ];

        $result += ['pager' => $pager];

        return $result;
    }

    public function validate()
    {
        $valid = true;
        $errors = [];

        foreach (static::$rules as $property => $rule) {
            if ($rule['required'] && (!property_exists($this, $property) || empty($this->$property))) {
                array_push($errors, "$property is required");
                $valid = false;
            } elseif (property_exists($this, $property) && gettype($this->$property) instanceof $rule['type']) {
                array_push($errors, "$property has invalid type");
                $valid = false;
            }
        }

        return compact('valid', 'errors');
    }

    public function load($data)
    {
        foreach ($this->fields as $field) {
            if (isset($data[$field])) {
                $this->$field = $data[$field];
            }
        }

        return $this; // TODO check errors
    }
}
