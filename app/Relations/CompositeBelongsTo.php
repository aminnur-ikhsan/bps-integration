<?php

namespace App\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompositeBelongsTo extends BelongsTo
{
    protected $compositeLocalKey;
    protected $compositeOwnerKey;

    public function __construct($query, $child, $foreignKey, $ownerKey, $compositeLocalKey, $compositeOwnerKey, $relation)
    {
        $this->compositeLocalKey = $compositeLocalKey;
        $this->compositeOwnerKey = $compositeOwnerKey;
        parent::__construct($query, $child, $foreignKey, $ownerKey, $relation);
    }

    public function addConstraints()
    {
        if (static::$constraints) {
            parent::addConstraints();
            $this->query->where($this->compositeOwnerKey, '=', $this->child->{$this->compositeLocalKey});
        }
    }

    public function addEagerConstraints(array $models)
    {
        parent::addEagerConstraints($models);
        $compositeKeys = collect($models)->pluck($this->compositeLocalKey)->filter()->unique()->values()->all();
        $this->query->whereIn($this->compositeOwnerKey, $compositeKeys);
    }

    public function match(array $models, Collection $results, $relation)
    {
        $dictionary = [];
        foreach ($results as $result) {
            $key = $result->getAttribute($this->ownerKey).'-'.$result->getAttribute($this->compositeOwnerKey);
            $dictionary[$key] = $result;
        }

        foreach ($models as $model) {
            $key = $model->getAttribute($this->foreignKey).'-'.$model->getAttribute($this->compositeLocalKey);
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key]);
            }
        }

        return $models;
    }
}
