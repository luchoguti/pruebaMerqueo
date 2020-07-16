<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Orders;
use App\Models\Providers;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    protected $inventory;
    protected $orders;
    protected $providers;
    protected $balances;

    public function __construct()
    {
        $this->orders = new Orders();
        $this->inventory = new Inventory();
        $this->providers = new Providers();
    }

    public function stockOrdersId(int $idOrders)
    {
        $ordersWithInv = $this->calculateStockProviders ($idOrders);
        return response()->json($ordersWithInv, 201);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function stockOrders()
    {
        $ordersWithInv = $this->calculateStock ('');
        return response()->json($ordersWithInv, 201);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Support\Collection
     */
    public function calculateInventory(Request $request)
    {
        $this->inventory->initDataInventory ($request['dataInventory']);
        $this->orders->initDataOrders (2,$request['dataOrders']);
        $this->balances = $this->inventory->getInventory ();
        $except = ['date'];
        $this->inventoryVSOrders ();
        $inventory= collect (array_values ($this->balances))
            ->map (function ($item) use ($except) {
                return collect($item)->except ($except)->toArray ();
            });
        $inventoryToDate['inventoryToDate'] = $inventory;
        return response()->json($inventoryToDate, 201);
    }

    /**
     * @param string $filter
     */
    private function inventoryVSOrdersSupply()
    {
        $orders = $this->orders->getOrders ();
        $ordersFilter = array();
        $supplyProduct = $this->orders->getOrders ();
        foreach ($orders as $index => $order) {
            for ($i=0; $i < count ($order['products']); $i++){
                $result = $this->calculateInvVsOrder ($order['products'], $i, $ordersFilter, $index,$supplyProduct);
                $supplyProduct = $result[1];
            }
            $supplyProduct[$index]['products'] = array_values($supplyProduct[$index]['products']);
            if(empty($supplyProduct[$index]['products'])){
                unset($supplyProduct[$index]);
            }
        }
        return $supplyProduct;
    }

    /**
     * @return mixed
     */
    private function inventoryVSOrders()
    {
        $orders = $this->orders->getOrders ();
        $ordersFilter = $this->orders->getOrders ();
        $supplyProduct = array();
        foreach ($orders as $index => $order) {
            for ($i=0; $i < count ($order['products']); $i++){
                $result = $this->calculateInvVsOrder ($order['products'], $i, $ordersFilter, $index,$supplyProduct);
                $ordersFilter = $result[0];
            }
            $ordersFilter[$index]['products'] = array_values($ordersFilter[$index]['products']);
            if(empty($ordersFilter[$index]['products'])){
                unset($ordersFilter[$index]);
            }
        }
        return $ordersFilter;
    }

    /**
     * @param $order
     * @param int $i
     * @param $ordersFilter
     * @param $index
     * @param array $supplyProduct
     * @return mixed
     */
    private function calculateInvVsOrder($order, int $i, $ordersFilter, $index, array $supplyProduct)
    {
        $product = $order[$i]['id'];
        $quantity = $order[$i]['quantity'];
        $inventory = collect ($this->balances)
            ->filter (function ($item) use ($product) {
                return $item['id'] == $product;
            });
        if (!$inventory->isEmpty ()) {
            $quantityInv = $inventory->flatten ()->get (0);
            if ($quantityInv >= $quantity) {
                $operation = $quantityInv - $quantity;
                $this->balances[$inventory->keys ()->first ()]['quantity'] = $operation;
                unset($supplyProduct[$index]['products'][$i]);
            } else {
                unset($ordersFilter[$index]['products'][$i]);
            }
        } else {
            unset($ordersFilter[$index]['products'][$i]);
        }
        $resultOperation = array();
        $resultOperation[] = $ordersFilter;
        $resultOperation[] = $supplyProduct;
        return $resultOperation;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    private function calculateStock(): \Illuminate\Support\Collection
    {
        $this->inventory->initDataInventory ('');
        $this->orders->initDataOrders (0,'');
        $except = ['priority', 'address', 'user', 'deliveryDate'];
        $this->balances = $this->inventory->getInventory ();
        return collect (array_values ($this->inventoryVSOrders ()))
            ->map (function ($item) use ($except) {
                $item = collect ($item);
                return $item->except ($except)->toArray ();
            });
    }
    private function calculateStockProviders(int $idOrders){
        $this->inventory->initDataInventory ('');
        $this->orders->initDataOrders (1,$idOrders);
        $this->balances = $this->inventory->getInventory ();
        $except = ['address', 'user','deliveryDate'];
        $supplyProvider = $this->supplyProviders ($except);
        $stock= collect (array_values ($this->inventoryVSOrders ()))
            ->whenNotEmpty(function ($collection) use ($except) {
                return $collection->map (function ($item) use ($except) {
                    return collect ($item)->except ($except)->toArray ();
                });
            })->toArray ();
        $stockInv['stockInventory'] = $stock;
        return collect ($supplyProvider)->merge (collect ($stockInv));
    }

    /**
     * @param array $except
     * @return mixed
     */
    private function supplyProviders(array $except)
    {
        $supplyProvider = array();
        $products = array_values ($this->inventoryVSOrdersSupply ());
        $provProd=$this->providersVSProducts($products);
        $supply = collect ($provProd)
            ->whenNotEmpty (function ($collection) use ($except) {
                return $collection->map (function ($item) use ($except) {
                    return collect ($item)->except ($except)->toArray ();
                });
            })->toArray ();
        $supplyProvider['supplyProvider'] = $supply;
        return $supplyProvider;
    }

    private function providersVSProducts(array $products)
    {
        $this->providers->initDataProviders();
        $providersProd = $this->providers->getProviders ();
        $listProducts = $products;
        for ($j=0;$j< count ($products); $j++){
            $listProducts[$j]['providers'] = array();
            foreach ($products[$j]['products'] as $index => $product) {
                $listProducts = $this->calculateProvidersVSProducts ($providersProd, $product, $listProducts, $j, $index);
            }
            unset($listProducts[$j]['products']);
        }

        return $listProducts;
    }

    /**
     * @param string $name
     * @param array $listProducts
     * @param int $j
     * @param $index
     * @param int $id
     * @return array
     */
    private function createDataProductsVsProvider(string $name, array $listProducts, int $j, $index, int $id): array
    {
        $newListProvider = array();
        if (isset($listProducts[$j]['providers'][$id])) {
            array_push ($listProducts[$j]['providers'][$id]['products'], $listProducts[$j]['products'][$index]);
        } else {
            $newListProvider['idProviders'] = $id;
            $newListProvider['nameProviders'] = $name;
            $newListProvider['products'][] = $listProducts[$j]['products'][$index];
            $listProducts[$j]['providers'][$id] = $newListProvider;
        }
        return $listProducts;
    }

    /**
     * @param $providersProd
     * @param $product
     * @param array $listProducts
     * @param int $j
     * @param $index
     * @return array
     */
    protected function calculateProvidersVSProducts($providersProd, $product, array $listProducts, int $j, $index): array
    {
        $search = collect ($providersProd)
            ->map (function ($prodProv) use ($product) {
                $data = collect ($prodProv['products'])
                    ->where ('productId', '=', $product['id']);
                if ($data->count () > 0) {
                    return collect ($prodProv)
                        ->except ('products')->toArray ();
                }
            })->filter ();
        if ($search->isNotEmpty ()) {
            $id = $search->flatten ()->get (0);
            $name = $search->flatten ()->get (1);
            $listProducts = $this->createDataProductsVsProvider ($name, $listProducts, $j, $index, $id);
        } else {
            $id = 0;
            $name = "withOutProvider";
            $listProducts = $this->createDataProductsVsProvider ($name, $listProducts, $j, $index, $id);
        }
        return $listProducts;
    }
}
