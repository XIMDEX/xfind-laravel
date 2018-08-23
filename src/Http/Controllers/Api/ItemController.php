<?php

namespace Xfind\Http\Controllers\api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Xfind\Core\Utils\DateHelpers;
use Xfind\Core\Utils\ArrayHelpers;

use Illuminate\Support\Facades\Request as StaticRequest;
use Xfind\Models\Item;

class ItemController extends Controller
{
    protected $request;
    protected $response;
    protected $model = Item::class;


    public static $paramsToModel = [
        'query' => 'setQuery',
        'limit' => 'setLimitPerPage',
        'start' => 'setStart',
        'page' => 'setPage'
    ];

    public function __construct()
    {
        $this->model = app()->make($this->model);
        $this->middleware('xml');
        $this->getQueryParams();
    }


    public function index()
    {
        $data = $this->model
            ->find()
            ->highlight()
            ->paginate();
        return $data;
    }

    public function update(Request $request)
    {
        $data = $request->all();
        if (!$data) { // IF not xml try with json
            $data = $request->json()->all();
        }

        $this->prepareData($data);
        ['valid' => $valid, 'errors' => $errors] = $this->model->load($data)->validate();
        if ($valid) {
            $result = $this->model->createOrUpdate();
            $res = $result ? ['updated', 200] : ['fail', 400];
        } else {
            $res = [json_encode(['errors' => $errors]), 400];
        }

        return response($res[0], $res[1]);
    }

    protected function getFacets()
    {
        return $this->model->getFacets();
    }

    protected function getQueryParams()
    {
        $queryParams = StaticRequest::all();

        $type = 'AND';
        if (array_key_exists('exclude', $queryParams)) {
            $type = ($queryParams['exclude'] === true | $queryParams['exclude'] === 'true') ? 'AND' : 'OR';
        }

        $params = [
            'limit' => 20,
            'start' => 0,
            'page' => 1
        ];

        $sort = [];
        $query = [];

        foreach ($queryParams as $param => $value) {
            if (array_key_exists($param, $params)) {
                $params[$param] = $queryParams[$param];
                $this->setQueryParamsToModel($param, $queryParams[$param]);
            } elseif (in_array($param, $this->model->getFields())) {
                $query[] = "$param:$value";
            } elseif (substr($param, 0, 5) === 'sort_') {
                $sort[str_replace('sort_', '', $param)] = $value;
            }
        }

        $query = count($query) > 0 ? implode($query, " $type ") : "*:*";
        $this->setQueryParamsToModel('query', $query);
        $this->model->setSort($sort);

        return $params;
    }

    protected function prepareData(&$data)
    {
        if (isset($data['@attributes'])) {
            $data = array_merge($data['@attributes'], $data);
            unset($data['@attributes']);
        }

        $creationDate = ArrayHelpers::getProperty($data, 'creation_date', '');
        $updateDate = ArrayHelpers::getProperty($data, 'update_date', '');

        $data['creation_date'] = DateHelpers::parse($creationDate);
        $data['creation_date'] = DateHelpers::parse($updateDate);

        $data1 = [];
        foreach ($data as $key => $value) {
            $data1[strtolower($key)] = $value;
        }
        $data = $data1;
        return $data;
    }

    private function setQueryParamsToModel($param, $value)
    {
        if (array_key_exists($param, static::$paramsToModel)) {
            $func = static::$paramsToModel[$param];
            $this->model->$func($value);
        }
    }

    protected function setResponse($data = [], $json = false, $status = 200)
    {
        $this->response = $this->response->withStatus($status);
        $status = $this->response->getStatusCode();
        $message = $this->response->getReasonPhrase();

        $response = [
            'status' => $status,
            'data' => $data,
            'message' => $message
        ];

        return ($json) ? json_encode($response) : $response;
    }
}
