<?php

namespace App\Models\Concerns;

use App\Helpers\RandomNumber;
use Illuminate\Database\Eloquent\Model;

trait HasPublicNumber
{
    /**
     * Boot the trait: set _number on creating if not already set.
     */
    public static function bootHasPublicNumber(): void
    {
        static::creating(function (Model $model) {
            $column = $model->getRouteKeyName();
            if (empty($model->{$column})) {
                $model->{$column} = RandomNumber::generateUniqueFor($model->getTable(), $column);
            }
        });
    }
}
