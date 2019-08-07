<?php

namespace Xfind\Core\Database\SolrEloquent;

use Iterator;
use Solarium\Component\Facet\Field;

class FacetSettings implements Iterator
{
    /**
     * Facet sort type index.
     */
    public const SORT_INDEX = Field::SORT_INDEX;

    /**
     * Facet sort type count.
     */
    public const SORT_COUNT = Field::SORT_COUNT;

    /**
     * Facet method enum.
     */
    const METHOD_ENUM = Field::METHOD_ENUM;
    /**
     * Facet method fc.
     */
    const METHOD_FC = Field::METHOD_FC;

    /**
     * Postion to iterator element
     * 
     * @ignore
     */
    private $position;

    /**
     * Limit the terms on which to facet to those starting with the given 
     * prefix. This does not limit the query, only the facets.
     *
     * @var string|null
     */
    protected $prefix;

    /**
     * Limit the terms on which to facet to those containing the given substring. 
     * This does not limit the query, only the facets. Available since Solr 5.1.
     *
     * @var string|null
     */
    protected $contains;

    /**
     * If 'contains' is used, causes case to be ignored when matching the given 
     * substring against candidate facet terms.
     *
     * @var boolean|null
     */
    protected $containsIgnoreCase;

    /**
     * Sort order (sorted by count). Use one of the class constants.
     *
     * @var string|null
     */
    protected $sort;

    /**
     * Limit the facet counts.
     *
     * @var int|null
     */
    protected $limit;

    /**
     * Show facet count starting from this offset.
     *
     * @var int|null
     */
    protected $offset;

    /**
     * Minimal term count to be included in facet count results.
     *
     * @var int|null
     */
    protected $minCount;

    /**
     * Also make a count of all document that have no value for the facet field.
     *
     * @var boolean|null
     */
    protected $missing;

    /**
     * Use one of the class constants as value. 
     * 
     * @see http://wiki.apache.org/solr/SimpleFacetParameters#facet.method
     * @var [type]
     */
    protected $method;

    public function __construct(array $params = [])
    {
        $this->position = 0;

        foreach ($params as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @see https://www.php.net/manual/en/class.iterator.php
     * @return void
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Return the current element
     *
     * @see https://www.php.net/manual/en/class.iterator.php
     * @return void
     */
    public function current()
    {
        return $this->toArray()[$this->key()];
    }

    /**
     * Return the key of the current element
     *
     * @see https://www.php.net/manual/en/class.iterator.php
     * @return void
     */
    public function key()
    {
        return $this->keys()[$this->position];
    }

    /**
     * Move forward to next element
     *
     * @see https://www.php.net/manual/en/class.iterator.php
     * @return void
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * Checks if current position is valid
     *
     * @see https://www.php.net/manual/en/class.iterator.php
     * @return void
     */
    public function valid()
    {
        return count($this->keys()) > $this->position;
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }
    public function getPrefix()
    {
        return $this->prefix;
    }

    public function setContains($contains)
    {
        $this->contains = $contains;
        return $this;
    }
    public function getContains()
    {
        return $this->contains;
    }

    public function setContainsIgnoreCase($containsIgnoreCase)
    {
        $this->containsIgnoreCase = $containsIgnoreCase;
        return $this;
    }
    public function getContainsIgnoreCase()
    {
        return $this->containsIgnoreCase;
    }

    public function setSort($sort)
    {
        $this->sort = $sort;
        return $this;
    }
    public function getSort()
    {
        return $this->sort;
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }
    public function getLimit()
    {
        return $this->limit;
    }

    public function setOffset($offset)
    {
        $this->offset = $offset;
        return $this;
    }
    public function getOffset()
    {
        return $this->offset;
    }

    public function setMinCount($minCount)
    {
        $this->minCount = $minCount;
        return $this;
    }
    public function getMinCount()
    {
        return $this->minCount;
    }

    public function setMissing($missing)
    {
        $this->missing = $missing;
        return $this;
    }
    public function getMissing()
    {
        return $this->missing;
    }

    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }
    public function getMethod()
    {
        return $this->method;
    }

    public function toArray()
    {
        $elements = \Arr::except(get_object_vars($this), ['position']);
        return $elements;
    }

    protected function keys()
    {
        $data = array_keys($this->toArray());
        return $data;
    }
}
