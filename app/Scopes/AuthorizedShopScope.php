<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class AuthorizedShopScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
   
        if(auth()->id()){
            $authorized_shop_ids = auth()->user()->shops->pluck('id');
            // dd($authorized_shop_ids);
            $columnName = "shop_id";
            // if($model->getTable() == "shops"){
            //     $columnName = "id";
            // }

            $builder->whereIn($columnName,$authorized_shop_ids);
        }
    }
}