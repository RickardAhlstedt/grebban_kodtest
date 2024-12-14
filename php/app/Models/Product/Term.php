<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    protected $fillable = [
        'name',
        'code',
        'attribute_id',
        'parent_id'
    ];

    protected $table = 'attribute_terms';

}
