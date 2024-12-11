<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'id',
        'name',
        'attributes'
    ];

    /**
     * Create an instance of a product
     * @param int $id
     * @param string $name
     * @param array $attributes
     */
    public function __construct(int $id, string $name, array $attributes = []) {
        $this->id = $id;
        $this->name = $name;
        $this->attributes = $attributes;
    }
}
