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

use Solarium\Core\Client\Client;
use Solarium\Core\Client\Endpoint;
use Solarium\Core\Query\QueryInterface;
use Solarium\Core\Query\Result\ResultInterface;

class Solr extends Client
{
    public static $json_response = 'json';
    public static $default_response = 'default';

    private $responseType;
    private $conf;
    private $query;
    private $type;

    public function __construct()
    {
        $this->conf = [
            'endpoint' => [
                'solr' => [
                    'host' => config('xfind.solr.host'),
                    'port' => config('xfind.solr.port'),
                    'path' => config('xfind.solr.path'),
                    'core' => config('xfind.solr.core')
                ]
            ]
        ];

        $this->responseType = static::$default_response;
        parent::__construct($this->conf);
    }

    /**
     * Set the value of query
     *
     * @param $query
     */
    public function setQuery($query)
    {
        $this->query = $this->createQuery($query);
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
     * Get the value of type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the value of type
     *
     * @return  self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }


    /**
     * Get the value of responseType
     */
    public function getResponseType()
    {
        return $this->responseType;
    }

    /**
     * Set the value of responseType
     *
     * @return  self
     */
    public function setResponseType($responseType)
    {
        $this->responseType = $responseType;
        return $this;
    }

    /**
     * Execute a query.
     *
     * @param QueryInterface $query
     * @param Endpoint|string|null $endpoint
     *
     * @return ResultInterface
     */
    public function obtain(QueryInterface $query = null, $endpoint = null)
    {
        if (!is_null($query)) {
            $this->query = $query;
        }


        $result = $this->execute($this->query, $endpoint);

        $data = $result->getResponse()->getBody();

        // Facets
        $facets = $result->getFacetSet() ? $result->getFacetSet()->getFacets() : [];
        $resultFacet = [];
        foreach ($facets as $facet => $value) {
            $values = $value->getValues();
            if (is_array($values) && count($values) > 0) {
                $resultFacet[] = [
                    'key' => $facet,
                    'label' => $this->lang($facet),
                    'values' => $values,
                ];
            }
        }

        // Highlighting
        $highlighting = $result->getHighlighting() ? $result->getHighlighting() : [];
        $resultHighlighting = [];
        foreach ($highlighting as $key => $value) {
            $values = $value->getFields();
            if (is_array($values) && count($values) > 0) {
                $resultHighlighting[$key] = $values;
            }
        }

        $response = json_decode($data, true);

        if (array_key_exists('response', $response)) {
            $response = $response['response'];
        }
        $response['facets'] = $resultFacet;
        $response['highlighting'] = $resultHighlighting;

        return $response;
    }

    public function selectQuery(string $queryArgs = '*:*', array $filters = [])
    {
        $query = $this->createSelect();
        $query->setQuery($queryArgs);

        if (count($filters) > 0) {
            foreach ($filters as $key => $value) {
                $this->addFilter($query, $key, $value);
            }
        }

        $this->query = $query;
        return $this;
    }

    public function limit(int $limit, int $page = 0)
    {
        if ($page > 0) {
            $start = $limit * $page;
            $this->query->setStart($start);
        }
        $this->query->setRows($limit);

        return $this;
    }

    public function sort(array $sort)
    {
        foreach ($sort as $key => $value) {
            $this->query->addSort($key, $value);
        }
        return $this;
    }

    public function fields(array $fields)
    {
        $this->query->setFields($fields);
        return $this;
    }

    public function facetField($facet, $field)
    {
        $this->query->getFacetSet()
            ->createFacetField($facet)
            ->setField($field)
            ->setSort('index')
            ->setMinCount(1)
            ->setLimit(-1);
        return $this;
    }

    protected function lang($string)
    {
        $translation = config('xfind.translations') . ".{$string}";
        $result = __($translation);

        if ($result === $translation) {
            $result = $string;
        }
        return $result;
    }

    protected function addFilter(&$selectQuery, string $name, string $query)
    {
        $selectQuery->createFilterQuery($name)
            ->setQuery($query);
    }
}
