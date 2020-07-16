<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Orders extends Model
{
    /**
     * @var array|mixed
     */
    private $orders;

    /**
     * @return array|mixed
     */
    public function getOrders()
    {
        return $this->orders;
    }


    public function getDataJsonOrders(){
        $path = storage_path() . "\dataJson\orders_merqueo.json";
        return json_decode(file_get_contents($path), true);
    }
    /**
     * @param int $option
     * @param string $filter
     */
    public function initDataOrders(int $option, string $filter)
    {
        switch ($option){
            case 1:
                $data = $this->getDataJsonOrders ();
                $dataOrders = collect ($data['orders'])
                    ->where ('id', '=', $filter)->toArray ();
                $this->orders = $dataOrders;
                break;
            case 2:
                $data = $this->getDataJsonOrders ();
                $dataOrders = collect ($data['orders'])
                    ->where ('deliveryDate', '=', $filter)->toArray ();
                $this->orders = $dataOrders;
                break;
            default:
                $data = $this->getDataJsonOrders ();
                $this->orders = $data['orders'];
                break;
        }
    }


}
