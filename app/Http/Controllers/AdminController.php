<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Brand;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use File;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;


class AdminController extends Controller
{
public function index()
{
    return view("admin.index");
}
public function brands()
{
        $brands = Brand::orderBy('id','DESC')->paginate(10);
        return view("admin.brands",compact('brands'));
}
public function add_brand()
 {
      return view("admin.brand-add");
 }
 public function add_brand_store(Request $request)
{        
     $request->validate([
          'name' => 'required',
          'slug' => 'required|unique:brands,slug',
          'image' => 'mimes:png,jpg,jpeg|max:2048'
     ]);
     $brand = new Brand();
     $brand->name = $request->name;
     $brand->slug = Str::slug($request->name);
     $image = $request->file('image');
     $file_extention = $request->file('image')->extension();
     $file_name = Carbon::now()->timestamp . '.' . $file_extention;        
     $this->GenerateBrandThumbailImage($image,$file_name);
     $brand->image = $file_name;        
     $brand->save();
     return redirect()->route('admin.brands')->with('status','Record has been added successfully !');
}



public function edit_brand($id)
{
    $brand = Brand::find($id);
    return view('admin.brand-edit',compact('brand'));
}

public function update_brand(Request $request)
{
    $request->validate([
        'name' => 'required',
        'slug' => 'required|unique:brands,slug,'.$request->id,
        'image' => 'mimes:png,jpg,jpeg|max:2048'
    ]);
    $brand = Brand::find($request->id);
    $brand->name = $request->name;
    $brand->slug = $request->slug;
    if($request->hasFile('image'))
    {            
        if (File::exists(public_path('uploads/brands').'/'.$brand->image)) {
            File::delete(public_path('uploads/brands').'/'.$brand->image);
        }
        $image = $request->file('image');
        $file_extention = $request->file('image')->extension();
        $file_name = Carbon::now()->timestamp . '.' . $file_extention;
        $this->GenerateBrandThumbailImage($image,$file_name);
        $brand->image = $file_name;
    }        
    $brand->save();        
    return redirect()->route('admin.brands')->with('status','Record has been updated successfully !');
}
public function GenerateBrandThumbailImage($image, $file_name)
{
    // Create directories if needed
    $thumbnailPath = public_path('uploads/brands/thumbnail');
    if (!file_exists($thumbnailPath)) {
        mkdir($thumbnailPath, 0755, true);
    }

    // Move original image
    $image->move(public_path('uploads/brands'), $file_name);

    // Create manager instance with GD driver
    $manager = new ImageManager(new Driver());
    
    // Read image - NOTE: 'make()' is now 'read()' in v3+
    $img = $manager->read(public_path('uploads/brands/'.$file_name));
    
    // Process and save thumbnail
    $img->cover(100, 100)->save($thumbnailPath.'/'.$file_name);
}

public function delete_brand($id)
{
    $brand = Brand::find($id);
    if (File::exists(public_path('uploads/brands').'/'.$brand->image)) {
        File::delete(public_path('uploads/brands').'/'.$brand->image);
    }
    $brand->delete();
    return redirect()->route('admin.brands')->with('status','Record has been deleted successfully !');
}


public function categories()
 {
        $categories = Category::orderBy('id','DESC')->paginate(10);
        return view("admin.categories",compact('categories'));
 }
 public function add_category()
 {
      return view("admin.category-add");
 }
 public function add_category_store(Request $request)
{        
    $request->validate([
        'name' => 'required',
        'slug' => 'required|unique:categories,slug',
        'image' => 'mimes:png,jpg,jpeg|max:2048'
    ]);
    $category = new Category();
    $category->name = $request->name;
    $category->slug = Str::slug($request->name);
    $image = $request->file('image');
    $file_extention = $request->file('image')->extension();
    $file_name = Carbon::now()->timestamp . '.' . $file_extention;
    $this->GenerateCategoryThumbailImage($image,$file_name);
    $category->image = $file_name;        
    $category->save();
    return redirect()->route('admin.categories')->with('status','Record has been added successfully !');
}

 public function edit_category($id)
 {
    $category = Category::find($id);
    return view('admin.category-edit',compact('category'));
 }
 public function update_category(Request $request)
 {
    $request->validate([
        'name' => 'required',
        'slug' => 'required|unique:categories,slug,'.$request->id,
        'image' => 'mimes:png,jpg,jpeg|max:2048'
    ]);
    $category = Category::find($request->id);
    $category->name = $request->name;
    $category->slug = $request->slug;
    if($request->hasFile('image'))
    {            
        if (File::exists(public_path('uploads/categories').'/'.$category->image)) {
            File::delete(public_path('uploads/categories').'/'.$category->image);
        }
        $image = $request->file('image');
        $file_extention = $request->file('image')->extension();
        $file_name = Carbon::now()->timestamp . '.' . $file_extention;
        $this->GenerateCategoryThumbailImage($image,$file_name);
        $category->image = $file_name;
    }        
    $category->save();        
    return redirect()->route('admin.categories')->with('status','Record has been updated successfully !');
 }
 public function GenerateCategoryThumbailImage($image, $file_name)
 {
   // Create directories if needed
   $thumbnailPath = public_path('uploads/categories/thumbnail');
   if (!file_exists($thumbnailPath)) {
       mkdir($thumbnailPath, 0755, true);
   }

   // Move original image
   $image->move(public_path('uploads/categories'), $file_name);

   // Create manager instance with GD driver
   $manager = new ImageManager(new Driver());
   
   // Read image - NOTE: 'make()' is now 'read()' in v3+
   $img = $manager->read(public_path('uploads/categories/'.$file_name));
   
   // Process and save thumbnail
   $img->cover(100, 100)->save($thumbnailPath.'/'.$file_name);
}

 public function delete_category($id)
 {
    $category = Category::find($id);
    if (File::exists(public_path('uploads/categories').'/'.$category->image)) {
        File::delete(public_path('uploads/categories').'/'.$category->image);
    }
    $category->delete();
    return redirect()->route('admin.categories')->with('status','Record has been deleted successfully !');
 }


 public function products()
 {
     $products = Product::OrderBy('created_at','DESC')->paginate(10);        
     return view("admin.products",compact('products'));
 }

 public function add_product()
{
    $categories = Category::Select('id','name')->orderBy('name')->get();
    $brands = Brand::Select('id','name')->orderBy('name')->get();


    return view("admin.product-add",compact('categories','brands'));
}


public function product_store(Request $request)
{
    $request->validate([
        'name'=>'required',
        'slug'=>'required|unique:products,slug',
        'category_id'=>'required',
        'brand_id'=>'required',            
        'short_description'=>'required',
        'description'=>'required',
        'regular_price'=>'required',
        'sale_price'=>'required',
        'SKU'=>'required',
        'stock_status'=>'required',
        'featured'=>'required',
        'quantity'=>'required',
        'image'=>'required|mimes:png,jpg,jpeg|max:2048'            
    ]);
    $product = new Product();
    $product->name = $request->name;
    $product->slug = Str::slug($request->name);
    $product->short_description = $request->short_description;
    $product->description = $request->description;
    $product->regular_price = $request->regular_price;
    $product->sale_price = $request->sale_price;
    $product->SKU = $request->SKU;
    $product->stock_status = $request->stock_status;
    $product->featured = $request->featured;
    $product->quantity = $request->quantity;
    $current_timestamp = Carbon::now()->timestamp;
    if($request->hasFile('image'))
    {        
        if (File::exists(public_path('uploads/products').'/'.$product->image)) {
            File::delete(public_path('uploads/products').'/'.$product->image);
        }
        if (File::exists(public_path('uploads/products/thumbnails').'/'.$product->image)) {
            File::delete(public_path('uploads/products/thumbnails').'/'.$product->image);
        }            
    
        $image = $request->file('image');
        $imageName = $current_timestamp.'.'.$image->extension();
        $this->GenerateThumbnailImage($image,$imageName);            
        $product->image = $imageName;
    }
    $gallery_arr = array();
    $gallery_images = "";
    $counter = 1;
    if($request->hasFile('images'))
    {
        $oldGImages = explode(",",$product->images);
        foreach($oldGImages as $gimage)
        {
            if (File::exists(public_path('uploads/products').'/'.trim($gimage))) {
                File::delete(public_path('uploads/products').'/'.trim($gimage));
            }
            if (File::exists(public_path('uploads/products/thumbails').'/'.trim($gimage))) {
                File::delete(public_path('uploads/products/thumbails').'/'.trim($gimage));
            }
        }
        $allowedfileExtension=['jpg','png','jpeg'];
        $files = $request->file('images');
        foreach($files as $file){                
            $gextension = $file->getClientOriginalExtension();                                
            $check=in_array($gextension,$allowedfileExtension);            
            if($check)
            {
                $gfilename = $current_timestamp . "-" . $counter . "." . $gextension;   
                $this->GenerateThumbnailImage($file,$gfilename);                    
                array_push($gallery_arr,$gfilename);
                $counter = $counter + 1;
            }
        }
        $gallery_images = implode(',', $gallery_arr);
    }
    $product->images = $gallery_images;
    $product->category_id = $request->category_id;
    $product->brand_id = $request->brand_id;
    $product->save();
    return redirect()->route('admin.products')->with('status','Record has been added successfully !');
}

public function edit_product($id)
{
    $product = Product::find($id);
    $categories = Category::Select('id','name')->orderBy('name')->get();
    $brands = Brand::Select('id','name')->orderBy('name')->get();


    return view('admin.product-edit',compact('product','categories','brands'));
}

public function update_product(Request $request)
{
    $request->validate([
        'name'=>'required',
        'slug'=>'required|unique:products,slug,'.$request->id,
        'category_id'=>'required',
        'brand_id'=>'required',            
        'short_description'=>'required',
        'description'=>'required',
        'regular_price'=>'required',
        'sale_price'=>'required',
        'SKU'=>'required',
        'stock_status'=>'required',
        'featured'=>'required',
        'quantity'=>'required',
        'image'=>'required|mimes:png,jpg,jpeg|max:2048'            
    ]);
    
    $product = Product::find($request->id);
    $product->name = $request->name;
    $product->slug = Str::slug($request->name);
    $product->short_description = $request->short_description;
    $product->description = $request->description;
    $product->regular_price = $request->regular_price;
    $product->sale_price = $request->sale_price;
    $product->SKU = $request->SKU;
    $product->stock_status = $request->stock_status;
    $product->featured = $request->featured;
    $product->quantity = $request->quantity;
    $current_timestamp = Carbon::now()->timestamp;
    
    if($request->hasFile('image'))
    {        
        $product->image = $request->image;
        $file_extention = $request->file('image')->extension();            
        $file_name = $current_timestamp . '.' . $file_extention;
        $path = $request->image->storeAs('products', $file_name, 'uploads/products/thumbnail');
        $product->image = $path;
    }
    $gallery_arr = array();
    $gallery_images = "";
    $counter = 1;
    if($request->hasFile('images'))
    {
        $allowedfileExtension=['jpg','png','jpeg'];
        $files = $request->file('images');
        foreach($files as $file){                
            $gextension = $file->getClientOriginalExtension();                                
            $check=in_array($gextension,$allowedfileExtension);            
            if($check)
            {
                $gfilename = $current_timestamp . "-" . $counter . "." . $gextension;                    
                $gpath = $file->storeAs('products', $gfilename, 'uploads/products/thumbnail');
                array_push($gallery_arr,$gpath);
                $counter = $counter + 1;
            }
        }
        $gallery_images = implode(', ', $gallery_arr);
    }
    $product->images = $gallery_images;
    $product->save();       
    return redirect()->route('admin.products')->with('status','Record has been updated successfully !');
}

public function GenerateThumbnailImage($image,$file_name)
{
      // Create directories if needed
      $thumbnailPath = public_path('uploads/products/thumbnail');
      if (!file_exists($thumbnailPath)) {
          mkdir($thumbnailPath, 0755, true);
      }
   
      // Move original image
      $image->move(public_path('uploads/products'), $file_name);
   
      // Create manager instance with GD driver
      $manager = new ImageManager(new Driver());
      
      // Read image - NOTE: 'make()' is now 'read()' in v3+
      $img = $manager->read(public_path('uploads/products/'.$file_name));
      
      // Process and save thumbnail
      $img->cover(100, 100)->save($thumbnailPath.'/'.$file_name);
}


function product_delete($id)
{
    $product = Product::find($id);
    $product->delete();
    return redirect()->route('admin.products')->with('status','Record has been deleted successfully !');
}   


public function coupons()
{
        $coupons = Coupon::orderBy("expiry_date","DESC")->paginate(12);
        return view("admin.coupons",compact("coupons"));
}

public function add_coupon()
{        
    return view("admin.coupon-add");
}
public function add_coupon_store(Request $request)
{
    $request->validate([
        'code' => 'required',
        'type' => 'required',
        'value' => 'required|numeric',
        'cart_value' => 'required|numeric',
        'expiry_date' => 'required|date'
    ]);
    $coupon = new Coupon();
    $coupon->code = $request->code;
    $coupon->type = $request->type;
    $coupon->value = $request->value;
    $coupon->cart_value = $request->cart_value;
    $coupon->expiry_date = $request->expiry_date;
    $coupon->save();
    return redirect()->route("admin.coupons")->with('status','Record has been added successfully !');
}
public function edit_coupon($id)
{
       $coupon = Coupon::find($id);
       return view('admin.coupon-edit',compact('coupon'));
}
public function update_coupon(Request $request)
{
       $request->validate([
       'code' => 'required',
       'type' => 'required',
       'value' => 'required|numeric',
       'cart_value' => 'required|numeric',
       'expiry_date' => 'required|date'
       ]);
       $coupon = Coupon::find($request->id);
       $coupon->code = $request->code;
       $coupon->type = $request->type;
       $coupon->value = $request->value;
       $coupon->cart_value = $request->cart_value;
       $coupon->expiry_date = $request->expiry_date;               
       $coupon->save();           
       return redirect()->route('admin.coupons')->with('status','Record has been updated successfully !');
}


public function delete_coupon($id)
{
        $coupon = Coupon::find($id);        
        $coupon->delete();
        return redirect()->route('admin.coupons')->with('status','Record has been deleted successfully !');
}


public function orders()
{
        $orders = Order::orderBy('created_at','DESC')->paginate(12);
        return view("admin.orders",compact('orders'));
}


public function order_items($order_id){
    $order = Order::find($order_id);
      $orderitems = OrderItem::where('order_id',$order_id)->orderBy('id')->paginate(12);
      $transaction = Transaction::where('order_id',$order_id)->first();



      return view("admin.order-details",compact('order','orderitems','transaction'));
}


public function update_order_status(Request $request){        
    $order = Order::find($request->order_id);
    $order->status = $request->order_status;
    if($request->order_status=='delivered')
    {
        $order->delivered_date = Carbon::now();
    }
    else if($request->order_status=='canceled')
    {
        $order->canceled_date = Carbon::now();
    }        
    $order->save();
    if($request->order_status=='delivered')
    {
        $transaction = Transaction::where('order_id',$request->order_id)->first();
        $transaction->status = "approved";
        $transaction->save();
    }
    return back()->with("status", "Status changed successfully!");
}



}