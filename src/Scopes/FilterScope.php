<?php
namespace AndersonScherdovski\Base\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class FilterScope implements Scope
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
        $filters = request()->get('filters', []);

        self::addFilters($filters, $builder);
    }

    /**
     * Add filter scope.
     *
     * @param $filters
     * @param $builder
     */
    public static function addFilters($filters, $builder)
    {
        foreach ($filters as $key => $filter) {

            $array = explode(' ', $filter);
            $value = substr($filter, (strlen($array[0]) + strlen($array[1]) + 2));
            $column = $array[0];
            $operator = $array[1];

            $columnArray = explode('.', $column);
            $multipleColumnsArray = explode('||', $column);

            if (count($multipleColumnsArray) > 1) {
                $builder->where(function ($query) use ($column, $operator, $value, $multipleColumnsArray) {
                    foreach ($multipleColumnsArray as $column) {

                        $columnArray = explode('.', $column);
                        if (count($columnArray) > 1) {
                            $table = '';
                            $lastIndex = count($columnArray) - 1;
                            $column = $columnArray[$lastIndex];
                            unset($columnArray[$lastIndex]);

                            foreach ($columnArray as $item) {
                                if (!empty($table)) {
                                    $table .= '.';
                                }
                                $table .= $item;
                            }

                            self::addQuery($query, $column, $operator, $value, $table, false);
                        } else {
                            self::addQuery($query, $column, $operator, $value, null, false);
                        }
                    }
                });
            } elseif (count($columnArray) > 1) {
                $table = '';
                $lastIndex = count($columnArray) - 1;
                $column = $columnArray[$lastIndex];
                unset($columnArray[$lastIndex]);

                foreach ($columnArray as $item) {
                    if (!empty($table)) {
                        $table .= '.';
                    }
                    $table .= $item;
                }

                self::addQuery($builder, $column, $operator, $value, $table);
            } else {
                self::addQuery($builder, $column, $operator, $value);
            }
        }
    }

    /**
     * Add query to params.
     *
     * @param $builder
     * @param $column
     * @param $operator
     * @param $value
     * @param null $table
     * @param bool $and
     */
    static function addQuery($builder, $column, $operator, $value, $table = null, $and = true)
    {

        if ($table) {
            if ($and) {
                $builder->whereHas($table, function ($query) use ($column, $operator, $value, $and) {
                    if ($operator == 'null') {
                        $query->whereNull($column);
                    } else if ($operator == 'not_null') {
                        $query->whereNotNull($column);
                    } else {
                        if ($operator == 'LIKE') {
                            $value = "%$value%";
                            $query->where($column, $operator, $value);
                            return;
                        }
                        $query->where($column, $operator, $value);
                    }
                });
            } else {
                $builder->orWhereHas($table, function ($query) use ($column, $operator, $value, $and) {
                    if ($operator == 'null') {
                        $query->whereNull($column);
                    } else if ($operator == 'not_null') {
                        $query->whereNotNull($column);
                    } else {
                        if ($operator == 'LIKE') {
                            $value = "%$value%";
                            $query->where($column, $operator, $value);
                            return;
                        }
                        $query->where($column, $operator, $value);
                    }
                });
            }
        } else {
            if ($operator == 'LIKE') {
                $value = "%$value%";
            }

            if ($and) {
                if ($operator == 'null') {
                    $builder->whereNull($column);
                } else if ($operator == 'not_null') {
                    $builder->whereNotNull($column);
                } else if ($operator == 'doesnt_have') {
                    // variavel é column mas na verdade é passado a table
                    $builder->doesnthave($column);
                } else if ($operator == 'has') {
                    // variavel é column mas na verdade é passado a table
                    $builder->has($column);
                } else {
                    if ($operator == 'LIKE') {
                        $builder->where($column, $operator, $value);
                    } else {
                        $builder->where($column, $operator, $value);
                    }
                }
            } else {
                if ($operator == 'null') {
                    $builder->orWhereNull($column);
                } else if ($operator == 'not_null') {
                    $builder->orWhereNotNull($column);
                } else {
                    if ($operator == 'LIKE') {
                        $builder->where($column, $operator, $value);
                    }
                    $builder->orWhere($column, $operator, $value);
                }
            }
        }
    }
}
