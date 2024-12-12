<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product\Attribute;
use App\Models\Product\Product;
use App\Models\Product\Term;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{

    public function __construct() {
        if(!Cache::get('products') || !Cache::get('attributes'))
            $this->fetchContent();
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

            foreach($attr['values'] as $value) {
                $term = new Term($value['name'], $value['code']);
                $termsByCode[$term->code] = $term;

                if($term->parentCode && isset($termsByCode[$term->parentCode])) {
                    $termsByCode[$term->parentCode]->subTerms[] = $term;
                } else {
                    $terms[] = $term;
                }
            }
            $tree[] = new Attribute($attr['name'], $attr['code'], $terms);
        }
        Cache::put('attributes', $tree, 30);
    }

    /**
     * Get a collection of products fetched from external sources
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function index(Request $request): Response {
        $productCache = Cache::get('products_txt');
        $products = [];
        $productCollection = new Collection();
        foreach($productCache as $prod) {
            $product = new Product($prod['id'], $prod['name'], []);
            $productCollection->add($product);
        }

        // $productCollection->sortBy(function($))

        // Implementation if no collection is to be used
        $totalProducts = count($products);
        $page = $request->get('page', 1);
        $pageSize = $request->get('page_size', 5);
        if($pageSize <= 0)
            $pageSize = 5;

        $pagesAvailable = $totalProducts / $pageSize;
        if($page > $pagesAvailable+1) {
            $page = 1;
        }

        $productChunks = array_chunk($products, $pageSize);

        $responseObject = [
            'products' => $productChunks[$page - 1],
            'page' => intval($page),
            'totalPages' => intval(ceil($pagesAvailable))
        ];

        return response($responseObject);

    }
}
