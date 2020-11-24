<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ShopeeProductModel;

class ShopeeProductController extends Controller
{
    public function getProductsDetail(){
        return (new ShopeeProductModel)->getItemsDetail();        
    }
}
