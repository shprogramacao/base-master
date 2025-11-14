<?php

namespace AndersonScherdovski\Base\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OrderScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $sort = request()->get('sort', 'id desc');

        if (!$sort)
            return;

        $sortArray = explode('.', $sort);

        if (count($sortArray) > 1) {
            $table = $sortArray[0];
            $sort = $sortArray[1];

            $arrayData = explode(' ', $sort);
            $column = $arrayData[0];
            $direction = $arrayData[1];

            $builder->whereHas($table, function ($query) use ($column, $direction) {
                $query->orderBy($column, $direction);
            });
        } else {
            $arrayData = explode(' ', $sort);
            $column = $arrayData[0];
            $direction = $arrayData[1];

            $builder->orderBy($column, $direction);
        }
    }
}
