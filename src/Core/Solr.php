<?php

namespace Xfind\Core;

use Solarium\Core\Client\Client;
use Solarium\Core\Client\Endpoint;
use Solarium\Core\Query\QueryInterface;
use Solarium\Core\Query\Result\ResultInterface;
use Solarium\Exception as SolrException;

class Solr extends Client
{
    public static $json_response = 'json';
    public static $default_response = 'default';

    private $responseType;
    private $conf;
    private $solarium;
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


    public function test()
    {
        $ping = $this->createPing();

        try {
            $result = $this->obtain($ping);
            return 'Successful connection with solr';
        } catch (SolrException $e) {
            return $e;
        }
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

    public function selectQuery(string $queryArgs = '*:*')
    {
        $query = $this->createSelect();
        $query->setQuery($queryArgs);
        $this->query = $query;
        return $this;
    }

    public function limit(int $limit, int $start = 0)
    {
        if ($start > 0) {
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
            ->setLimit(-1);
        return $this;
    }

    protected function lang($string) {
        $translation = config('xfind.translations') . ".{$string}";
        $result = __($translation);

        if ($result === $translation) {
            $result = $string;
        }
        return $result;
    }
}
