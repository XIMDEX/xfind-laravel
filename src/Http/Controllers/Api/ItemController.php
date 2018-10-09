<?php

namespace Xfind\Http\Controllers\api;

use Xfind\Models\Item;
use Illuminate\Http\Request;
use Xfind\Core\Utils\DateHelpers;

use Illuminate\Routing\Controller;
use Xfind\Core\Utils\ArrayHelpers;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Request as StaticRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ItemController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    
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
        $this->middleware(\XmlMiddleware\XmlRequestMiddleware::class);
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
            $type = ($queryParams['exclude'] === true | $queryParams['exclude'] === 'true') ? $type : 'OR';
        }

        $params = [
            'limit' => 20,
            'start' => 0,
            'page' => 1
        ];

        $sort = [];
        $query = [];

        foreach ($queryParams as $param => $value) {
            if (empty($value) || is_null($value)) {
                continue;
            }
            if (array_key_exists($param, $params)) {
                $params[$param] = $queryParams[$param];
                $this->setQueryParamsToModel($param, $queryParams[$param]);
            }   elseif ($param === $this->model::$search) {
                $query[] = "$param:($value)";
            }   elseif (in_array($param, $this->model->getFields())) {
                $query[] = $this->setParam($param, $value);
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

    protected function setParam(string $param, $value) : string
    {
        $values = explode(',', $value);
        $last = count($values) - 1;
        $result = '';
        
        foreach($values as $key => $val) {
            if (!starts_with($val, '`') && !ends_with($val, '`')) {
                $val = "\"$val\"";
            }   else {
                $val = str_replace('`', '', $val);
            }
            $result .= " $val " . (($key < $last) ? 'OR' : '');
        }

        $result = trim($result);
        return "$param:($result)";
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