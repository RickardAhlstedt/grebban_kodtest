<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product\Attribute;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{

    public function __construct() {
        if(!Cache::get('products') || !Cache::get('attributes'))
            $this->fetchContent(true);
    }
    /**
     * Fetch the content needed for processing
     * @param bool $cache
     * @return mixed
     */
    private function fetchContent(bool $cache = true): mixed {
        // Create a cache with short TTL (30 seconds) upon hitting this controller
        $productSource = config('app.sources.products');
        $attributeSource = config('app.sources.attribute_meta');

        // Fetch the content
        $client = new Client();
        $productResponse = $client->get($productSource);
        if($productResponse->getStatusCode() === 200) {
            $productSourceContent = json_decode($productResponse->getBody(), true);
        }
        $attributeResponse = $client->get($attributeSource);
        if($attributeResponse->getStatusCode() === 200) {
            $attributeSourceContent = json_decode($attributeResponse->getBody(), true);
        }

        if($productSourceContent) {
            Cache::put('products_txt', $productSourceContent, 30);
        }
        if($attributeSourceContent) {
            Cache::put('attributes_txt', $attributeSourceContent, 30);
        }
        if(!$cache) {
            return [
                'products' => $productSourceContent,
                'attributes' => $attributeSourceContent
            ];
        } else {
            return true;
        }
    }

    public function buildAttributeTree(): void {
        $data = [];
        $tree = [];
        if(!$data = Cache::get('attributes_txt')) {
            $this->fetchContent(true);
            $data = Cache::get('attributes_txt');
        }

        foreach($data as $attr) {
            $terms = [];
            $termsByCode = [];
            
        }
    }

    /**
     * Get a collection of products fetched from external sources
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function index(Request $request): void {
        
    }
}
