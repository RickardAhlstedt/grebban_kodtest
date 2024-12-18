<?php

namespace App\Helpers;

use App\Models\Product\Attribute;
use App\Models\Product\Product;
use App\Models\Product\Term;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class SourceHelper {
    public function fetchRemoteData(string $url): mixed {
        $client = new Client();
        $response = $client->get($url);
        if($response->getStatusCode() === 200) {
            return json_decode($response->getBody(), true);
        } else {
            return false;
        }
    }
    public static function checkRemoteForNewVersion() {
        $attributeURL = config('app.sources.attribute_meta');
        if(!$currentVersion = Cache::get('attributes_version')) {
            // No version has been stored, we need to fetch and process the attributes into the database
            SourceHelper::fetchAttributes();
            SourceHelper::fetchProducts();
        } else {
            $remoteVersion = '';
            if($remoteContent = (new SourceHelper)->fetchRemoteData($attributeURL)) {
                $remoteVersion = md5($remoteContent);
            }
            if($remoteVersion !== $currentVersion) {
                // Local copy has been outdated, we need to truncate the tables and fetch and process data again
                SourceHelper::fetchAttributes();
                SourceHelper::fetchProducts();
            }
        }
    }

    /**
     * Create attribute
     * @param array $attributes
     * @see App\Models\Product\Attribute
     * @return mixed App\Models\Product\Attribute on success, false on failure
     */
    private static function createAttribute(array $attributes): mixed {
        $attribute = new Attribute($attributes);
        if($attribute->save()) {
            return $attribute;
        } else {
            return false;
        }
    }

    public static function fetchAttributes() {
        $attributeURL = config('app.sources.attribute_meta');
        if($terms = Term::all()) {
            // Delete terms and attributes, using the static DB-class, as we're relying on sqlite
            DB::statement('PRAGMA foreign_keys = OFF;');
            DB::table('attribute_terms')->delete();
            DB::table('attributes')->delete();
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if($attributeContent = (new SourceHelper)->fetchRemoteData($attributeURL)) {
            foreach($attributeContent as $data) {
                $terms = $data['values'];
                if($attribute = SourceHelper::createAttribute([
                    'name' => $data['name'],
                    'code' => $data['code']
                ])) {
                    $attributeId = $attribute->id;
                } else {
                    continue;
                }
                $orphanedTerms = [];
                foreach($terms as $termData) {
                    // Let's first check if the code contains more than one underscore
                    if(substr_count($termData['code'], '_') > 1) {
                        $termParts = explode('_', $termData['code']);
                        $termCode = implode('_', array_slice($termParts, 0, count($termParts) - 1));
                        if($parent = Term::where('code', '=', $termCode)->first()) {
                            $parentId = $parent->id;
                            $term = new Term([
                                'name' => $termData['name'],
                                'code' => $termData['code'],
                                'attribute_id' => $attribute->id,
                                'parent_id' => $parentId
                            ]);
                            $term->save();
                        } else {
                            $orphanedTerms[] = $termData;
                        }
                    } else {
                        $term = new Term([
                            'name' => $termData['name'],
                            'code' => $termData['code'],
                            'attribute_id' => $attribute->id
                        ]);
                        $term->save();
                    }
                }
                if($orphanedTerms) {
                    foreach($orphanedTerms as $orphan) {
                        $termParts = explode('_', $orphan['code']);
                        $termCode = implode('_', array_slice($termParts, 0, count($termParts) - 1));
                        if($parent = Term::where('code', '=', $termCode)->first()) {
                            $parentId = $parent->id;
                            $term = new Term([
                                'name' => $orphan['name'],
                                'code' => $orphan['code'],
                                'attribute_id' => $attribute->id,
                                'parent_id' => $parentId
                            ]);
                            $term->save();
                        }
                    }
                }
            }
        }

    }

    public static function fetchProducts() {
        $productURL = config('app.sources.products');
        if($products = Product::all()) {
            DB::statement('PRAGMA foreign_keys = OFF;');
            Product::query()->truncate();
            DB::statement('PRAGMA foreign_keys = ON;');
        }
        $attributeCache = [];
        if($productContent = (new SourceHelper)->fetchRemoteData($productURL)) {
            foreach($productContent as $data) {
                $attributes = [];
                foreach($data['attributes'] as $attributeCode => $termCode) {
                    $attribute = null;
                    // Split the $termCode by ',', and search for each term-part
                    $termCodeParts = explode(',', $termCode);
                    if(array_key_exists($attributeCode, $attributeCache)) {
                        $attribute = $attributeCache[$attributeCode];
                    } else {
                        $attribute = Attribute::where('code', '=', $attributeCode)->with('terms')->first();
                        $attributeCache[$attributeCode] = $attribute;
                    }

                    $attributeEntries = [];
                    if($attributeCode === 'color') {
                        foreach($termCodeParts as $termCode) {
                            $termName = $attribute->terms->where('code', $termCode)->first()->name;
                            $attributeEntries[] = [
                                'name' => $attribute->name,
                                'value' => $termName
                            ];
                        }
                    } else {
                        $categories = [];
                        foreach($termCodeParts as $termCode) {
                            $termName = $attribute->terms->where('code', $termCode)->first()->name;
                            $categories[] = $termName;
                        }
                        $attributeEntries[] = [
                            'name' => $attribute->name,
                            'value' => implode(' > ', $categories)
                        ];
                    }
                    $attributes[] = current($attributeEntries);
                }
                $product = new Product([
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'attributes' => json_encode($attributes)
                ]);
                $product->save();
            }
        }

    }

}
