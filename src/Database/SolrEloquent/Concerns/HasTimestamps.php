<?php

namespace Xfind\Database\SolrEloquent\Concerns;

trait HasTimestamps
{
    use \Illuminate\Database\Eloquent\Concerns\HasTimestamps;

    /**
     * Get the name of the "indexed at" column.
     *
     * @return string
     */
    public function getIndexedAtColumn()
    {
        return static::INDEXED_AT;
    }

        /**
     * Set the value of the "indexed at" attribute.
     *
     * @param  mixed  $value
     * @return $this
     */
    public function setIndexedAt($value)
    {
        $this->{static::INDEXED_AT} = $value;
        return $this;
    }

    /**
     * Update the creation and update timestamps.
     *
     * @return void
     */
    protected function updateTimestamps()
    {
        $time = $this->freshTimestamp();
        if (!is_null(static::INDEXED_AT)) {
            $this->setIndexedAt($time);
        }
    }
}