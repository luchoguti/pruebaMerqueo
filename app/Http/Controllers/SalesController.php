<?php

namespace App\Http\Controllers;

use App\Models\Orders;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    /**
     * @var Orders
     */
    private $orders;

    public function __construct()
    {
        $this->orders = new Orders();

    }
    /**
     * Display the specified resource.
     *
     * @param $dateReport
     * @return void
     */
    public function productBestSeller(string $dateReport)
    {
        $products = $this->dataOrders ($dateReport);
        $list = array_values($products->toArray ());
        $listProd = $this->listProduct($list);
        $sortProd = $this->sortProductBestSeller ($listProd);
        if(empty($sortProd)){
            $sortProd [] = 'without results';
        }
        return response()->json($sortProd, 201);
    }

    /**
     * @param string $dateReport
     * @return \Illuminate\Http\JsonResponse
     */
    public function productLeastSell(string $dateReport)
    {
        $products = $this->dataOrders ($dateReport);
        $list = array_values($products->toArray ());
        $listProd = $this->listProduct ($list);
        $sortProd = $this->sortProductLeastSeller ($listProd);
        if(empty($sortProd)){
            $sortProd [] = 'without results';
        }
        return response()->json($sortProd, 201);
    }

    /**
     * @param array $list
     * @return array
     */
    protected function listProduct(array $list)
    {
        $newProduct = array();
        for ($i=0; $i < count ($list); $i++){
            for ($j=0; $j < count ($list[$i]); $j++){
                if (isset($newProduct[$list[$i][$j]['id']]['cant_sale'])){
                    $newProduct [$list[$i][$j]['id']]['cant_sale'] += $list[$i][$j]['quantity'];
                }else{
                    $newProduct [$list[$i][$j]['id']]['cant_sale'] = $list[$i][$j]['quantity'];
                    $newProduct [$list[$i][$j]['id']]['name_product'] = $list[$i][$j]['name'];
                    $newProduct [$list[$i][$j]['id']]['id'] = $list[$i][$j]['id'];
                }
            }
        }
        return $newProduct;
    }

    /**
     * @param array $products
     * @return array
     */
    protected function sortProductBestSeller(array $products)
    {
        $sortProd=collect ($products)->sortByDesc('cant_sale');
        return array_values($sortProd->toArray ());
    }

    /**
     * @param array $products
     * @return array
     */
    protected function sortProductLeastSeller(array $products)
    {
        $sortProd=collect ($products)->sortBy('cant_sale');
        return array_values($sortProd->toArray ());
    }

    /**
     * @param string $dateReport
     * @return \Illuminate\Support\Collection
     */
    protected function dataOrders(string $dateReport): \Illuminate\Support\Collection
    {
        $this->orders->initDataOrders (0,'');
        return collect ($this->orders->getOrders ())
            ->where ('deliveryDate', '=', $dateReport)
            ->map (function ($item, $key) {
                return $item['products'];
            });
    }
}
