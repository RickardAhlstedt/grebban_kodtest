<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $fillable = [
        'name',
        'code',
        'values'
    ];

    public function __construct(string $name, string $code, array $values = []) {
        $this->name = $name;
        $this->code = $code;
        $this->values = $values;
    }

}
