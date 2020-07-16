<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    /**
     * @var mixed
     */
    private $inventory;

    /**
     * @return mixed
     */
    public function getInventory()
    {
        return $this->inventory;
    }

    public function getDataJsonInventory(){
        $path = storage_path() . "\dataJson\inventory_merqueo.json";
        return json_decode(file_get_contents($path), true);
    }

    public function initDataInventory(string $filter)
    {
        if(empty($filter)) {
            $dataInv = $this->getDataJsonInventory ();
            $this->inventory = $dataInv['inventory'];
        }else{
            $data = $this->getDataJsonInventory ();
            $dataInv = collect ($data['inventory'])
                ->where ('date','<=',$filter)->toArray ();
            $this->inventory = $dataInv;
        }

    }
}
