<?php

namespace App\Support\QueryBuilder\Sorts;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class RecommendedSorter implements Sort
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param bool                                  $descending
     * @param string                                $property
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function __invoke(Builder $query, bool $descending, string $property): Builder
    {
        // Remove any current orders
        $query->getQuery()->orders = null;

        return $query->inRandomOrder(
            $query->getModel()::getRandomSeed()
        );
    }
}
