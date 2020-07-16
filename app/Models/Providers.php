<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Providers extends Model
{
    /**
     * @var mixed
     */
    private $providers;

    /**
     * @return mixed
     */
    public function getProviders()
    {
        return $this->providers;
    }

    public function getDataJsonProviders(){
        $path = storage_path() . "\dataJson\providers_merqueo.json";
        return json_decode(file_get_contents($path), true);
    }
    public function initDataProviders()
    {
        $dataProv = $this->getDataJsonProviders ();
        $this->providers = $dataProv['providers'];
    }
}
