<?php

namespace App\Filters;

use App\Exceptions\FilterNameSpaceDoesNotExists;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method Builder filterBy(array $filters)
 */
trait Filterable
{
    public function scopeFilterBy($query, $filters)
    {
        if (! property_exists($this, 'filterNameSpace')) {
            throw new FilterNameSpaceDoesNotExists('filterNameSpace does not exists in class: '.get_class($this));
        }

        $filter = new FilterBuilder($query, $filters, $this->filterNameSpace);

        return $filter->apply();
    }
}
