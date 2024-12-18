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

    public function attribute() {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }

    public function parent() {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children() {
        return $this->hasMany(self::class, 'parent_id');
    }

}
