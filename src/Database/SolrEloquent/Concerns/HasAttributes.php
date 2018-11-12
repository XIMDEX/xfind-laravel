<?php

namespace Xfind\Database\SolrEloquent\Concerns;

use DateTime;

trait HasAttributes
{
    use \Illuminate\Database\Eloquent\Concerns\HasAttributes;

    /**
     * Get the casts array.
     *
     * @return array
     */
    public function getCasts()
    {
        return $this->casts;
    }

    /**
     * Get a relationship.
     *
     * @return mixed
     */
    public function getRelationValue()
    {
        return null;
    }

    /**
     * Get the attributes that should be converted to dates.
     *
     * @return array
     */
    public function getDates()
    {
        $defaults = [static::CREATED_AT, static::UPDATED_AT, static::INDEXED_AT];
        return $this->usesTimestamps()
                    ? array_unique(array_merge($this->dates, $defaults))
                    : $this->dates;
    }

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat()
    {
        return $this->dateFormat ?: config('xfind.date.format', DateTime::ISO8601);
    }

    /**
     * Convert a DateTime to a storable string.
     *
     * @param  \DateTime|int  $value
     * @return string
     */
    public function fromDateTime($value)
    {
        return empty($value) ? $value : $this->asDateTime($value)->format(
            $this->getDateFormat()
        );
    }
}