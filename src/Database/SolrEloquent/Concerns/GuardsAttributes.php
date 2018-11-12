<?php

namespace Xfind\Database\SolrEloquent\Concerns;

trait GuardsAttributes
{
    use \Illuminate\Database\Eloquent\Concerns\GuardsAttributes;
    
    /**
     * Get the fillable attributes for the model.
     *
     * @return array
     */
    public function getFillable()
    {
        return array_merge($this->fillable, [
            $this->getIndexedAtColumn(),
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn()
        ]);
    }
}