<?php

namespace App\Helpers;

use App\Models\Product\Attribute;
use App\Models\Product\Term;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SourceHelper {
    public function fetchRemoteData(string $url): mixed {
        $client = new Client();
        $response = $client->get($url);
        if($response->getStatusCode() === 200) {
            dump(json_decode($response->getBody(), true));
            return json_decode($response->getBody(), true);
        } else {
            return false;
        }
    }
    public static function checkAttributesForNewVersion() {
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
                $attribute = new Attribute([
                    'name' => $data['name'],
                    'code' => $data['code']
                ]);
                if($attribute->save()) {
                    $attributeId = $attribute->id;
                }
                $orphanedTerms = [];
                foreach($terms as $termData) {
                    // Let's first check if the code contains more than one underscore
                    if(substr_count($termData['code'], '_') > 1) {
                        $termParts = explode('_', $termData['code']);
                        $termCode = implode('_', array_slice($termParts, 0, 2));
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
                        $termCode = implode('_', array_slice($termParts, 0, 2));
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

    public static function fetchProducts() {}

}
