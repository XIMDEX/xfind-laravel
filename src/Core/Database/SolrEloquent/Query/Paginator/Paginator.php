<?php

namespace Xfind\Core\Database\SolrEloquent\Query\Paginator;

use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;

class Paginator extends AbstractPaginator implements Arrayable, JsonSerializable, Jsonable
{

    /**
     * Create a new paginator instance.
     *
     * @param  mixed  $items
     * @param  int  $perPage
     * @param  int|null  $currentPage
     * @param  array  $options (path, query, fragment, pageName)
     * @return void
     */
    public function __construct($items, $perPage, $total = null, $currentPage = null, array $customFields = [], array $options = [])
    {
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        $this->total = $total;
        $this->perPage = $perPage;
        $this->currentPage = $this->setCurrentPage($currentPage);
        $this->customFields = $customFields;

        $this->setItems($items);
    }

    /**
     * Get the current page for the request.
     *
     * @param  int  $currentPage
     * @return int
     */
    protected function setCurrentPage($currentPage)
    {
        $currentPage = $currentPage ?: static::resolveCurrentPage();

        return $this->isValidPageNumber($currentPage) ? (int)$currentPage : 1;
    }

    /**
     * Set the items for the paginator.
     *
     * @param  mixed  $items
     * @return void
     */
    protected function setItems($items)
    {
        $this->items = $items instanceof Collection ? $items : Collection::make($items);
        $this->hasMore = $this->count() > $this->perPage;
    }


    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {

        $pager = [
            'total' => $this->total,
            'per_page' => $this->perPage(),
            'current_page' => $this->currentPage(),
            "last_page" => $this->lastPage(),
            'next_page' => $this->nextPage(),
            'prev_page' => $this->prevPage(),
            'data' => []
        ];

        foreach ($this->items as $item) {
            $pager['data'][] = $item->toArray();
        }

        return $pager + $this->customFields;
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }
}
