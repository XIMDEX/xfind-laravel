<?php

namespace Xfind\Core\Database\SolrEloquent\Query\Paginator;

use Illuminate\Support\Traits\ForwardsCalls;

abstract class AbstractPaginator
{
    use ForwardsCalls;

    /**
     * Total of items in database.
     *
     * @var array
     */
    protected $total;

    /**
     * All of the items being paginated.
     *
     * @var array
     */
    protected $items;

    /**
     * The number of items to be shown per page.
     *
     * @var int
     */
    protected $perPage;

    /**
     * The current page being "viewed".
     *
     * @var int
     */
    protected $currentPage;

    /**
     * The query string variable used to store the page.
     *
     * @var string
     */
    protected $pageName = 'page';

    /**
     * The paginator options.
     *
     * @var array
     */
    protected $options;

    /**
     * The paginator custom fields.
     *
     * @var array
     */
    protected $customFields;

    /**
     * Determine if has more pages.
     *
     * @var bool
     */
    protected $hasMore;

    /**
     * The current page resolver callback.
     *
     * @var \Closure
     */
    protected static $currentPageResolver;

    /**
     * Determine if the given value is a valid page number.
     *
     * @param  int  $page
     * @return bool
     */
    protected function isValidPageNumber($page)
    {
        return $page >= 1 && filter_var($page, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Get the slice of items being paginated.
     *
     * @return array
     */
    public function items()
    {
        return $this->items;
    }

    /**
     * Get the number of the first item in the slice.
     *
     * @return int
     */
    public function firstItem()
    {
        return count($this->items) > 0 ? ($this->currentPage - 1) * $this->perPage + 1 : null;
    }

    /**
     * Get the number of the last item in the slice.
     *
     * @return int
     */
    public function lastItem()
    {
        return count($this->items) > 0 ? $this->firstItem() + $this->count() - 1 : null;
    }

    /**
     * Get the number of items shown per page.
     *
     * @return int
     */
    public function perPage()
    {
        return intval($this->perPage);
    }

    /**
     * Determine if has more pages availables
     *
     * @return boolean
     */
    public function hasMorePages(): bool
    {
        return $this->hasMore ?? $this->count() > $this->perPage;
    }

    /**
     * Determine if there are enough items to split into multiple pages.
     *
     * @return bool
     */
    public function hasPages()
    {
        return $this->currentPage() != 1 || $this->hasMorePages();
    }

    /**
     * Determine if the paginator is on the first page.
     *
     * @return bool
     */
    public function onFirstPage()
    {
        return $this->currentPage() <= 1;
    }

    /**
     * Get the current page.
     *
     * @return int
     */
    public function currentPage()
    {
        return $this->currentPage;
    }

    /**
     * Get the query string variable used to store the page.
     *
     * @return string
     */
    public function getPageName()
    {
        return $this->pageName;
    }

    /**
     * Set the query string variable used to store the page.
     *
     * @param  string  $name
     * @return $this
     */
    public function setPageName($name)
    {
        $this->pageName = $name;

        return $this;
    }


    /**
     * Resolve the current page or return the default value.
     *
     * @param  string  $pageName
     * @param  int  $default
     * @return int
     */
    public static function resolveCurrentPage($pageName = 'page', $default = 1)
    {
        $result = \Request::get($pageName, $default);

        return ctype_digit($result) ? intval($result) : $default;
    }

    public function nextPage()
    {
        $nextPage = $this->currentPage();
        if ($this->hasMorePages()) {
            $nextPage += 1;
        }

        if ($nextPage > $this->lastPage()) {
            $nextPage = $this->lastPage();
        }
        return $nextPage;
    }

    public function prevPage()
    {
        $prev = $this->currentPage() - 1;

        if ($prev > $this->lastPage()) {
            $prev = $this->lastPage();
        }

        if ($prev >= $this->nextPage()) {
            $prev -= 1;
        }

        if ($prev <= 0) {
            $prev = 1;
        }
        return $prev;
    }

    /**
     * Determine if the list of items is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return count($this->items) === 0;
    }

    /**
     * Determine if the list of items is not empty.
     *
     * @return bool
     */
    public function isNotEmpty()
    {
        return !$this->isEmpty();
    }

    /**
     * Get the number of items for the current page.
     *
     * @return int
     */
    public function count()
    {
        return $this->total;
    }

    /**
     * Get the paginator's underlying collection.
     *
     * @return array
     */
    public function getCollection()
    {
        return $this->items;
    }

    /**
     * Set the paginator's underlying collection.
     *
     * @param  array  $collection
     * @return $this
     */
    public function setCollection(Collection $collection)
    {
        $this->items = $collection;

        return $this;
    }

    /**
     * Return the last available page
     *
     * @return number
     */
    public function lastPage()
    {
        return ceil($this->total / $this->perPage());
    }

    /**
     * Get the paginator options.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Make dynamic calls into the collection.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->getCollection(), $method, $parameters);
    }

    /**
     * Create a new paginator instance.
     *
     * @param  array  $items
     * @param  int  $total
     * @param  int  $perPage
     * @param  int  $currentPage
     * @param  array  $options
     * @return array
     */
    public static function paginator($items, $total, $perPage, $currentPage, array $options = [])
    {
        return new static($items, $perPage, $total, $currentPage, $options);
    }
}