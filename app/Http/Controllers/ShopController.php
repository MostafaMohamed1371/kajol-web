<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;

class ShopController extends Controller
{
    public function index(Request $request)
    {        
        $size = $request->query('size')?$request->query('size'):12;
        $sorting = $request->query('sorting')?$request->query('sorting'):'default';               
        if($sorting=='date')   
        {
            $products = Product::orderBy('created_at','DESC')->paginate($size);  
        }
        else if($sorting=="price")
        {
            $products = Product::orderBy('regular_price','ASC')->paginate($size); 
        }
        else if($sorting=="price-desc")
        {
            $products = Product::orderBy('regular_price','DESC')->paginate($size); 
        }
        else{
            $products = Product::paginate($size);  
        }           
        $categories = Category::orderBy("name","ASC")->get();
        $brands = Brand::orderBy("name","ASC")->get();
        return view('shop',compact("products","size","sorting"));
    } 
public function product_details($product_slug)
{
    $product = Product::where("slug",$product_slug)->first();
    $rproducts = Product::where("slug","<>",$product_slug)->get()->take(8);



    return view('details',compact("product","rproducts"));
}

}
