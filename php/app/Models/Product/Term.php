<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    protected $fillable = [
        'name',
        'code',
        'parentCode',
        'subTerms'
    ];

    public function __construct(string $name, string $code) {
        $this->name = $name;
        $this->code = $code;
        $this->parentCode = $this->retrieveParent();
    }

    private function retrieveParent(): string|null {
        if(substr_count($this->code, '_') > 1) {
            return substr($this->code, 0, 5);
        }
        return null;
    }

}
