<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCollection;
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
    /**
     * Get a collection of products fetched from external sources
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function index(Request $request) {
        $query = Product::query();

        $perPage = $request->get('page_size', 5);
        $page = $request->get('page', 1);

        if($perPage && $perPage <= 0) {
            $collection = \App\Http\Resources\Product::collection($query->paginate($query->count()));
            $perPage = 5;
        } else {
            $collection = \App\Http\Resources\Product::collection($query->paginate($perPage));
        }

        $totalEntries = $query->count();
        $pagesAvailable = $totalEntries / $perPage;

        $resourceCollection = new ProductCollection($collection);
        $resourceCollection->with = [
            'page' => intval($page),
            'pages' => intval(ceil($pagesAvailable))
        ];

        return response()->json($resourceCollection, 200);

    }
}
