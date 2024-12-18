<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $fillable = [
        'name',
        'code'
    ];

    protected $table = 'attributes';

    public function terms(): mixed {
        return $this->hasMany( Term::class, 'attribute_id' );
    }

    public function getTermsById(int $id) {
        $attribute = self::with('terms')->find($id);
        if(!$attribute) {
            return null;
        }
        return $attribute->terms;
    }

}
