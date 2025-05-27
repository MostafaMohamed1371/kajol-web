<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Surfsidemedia\Shoppingcart\Facades\Cart;
use App\Models\Coupon;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use Illuminate\Support\Facades\Session;
class CartController extends Controller
{
    public function index()
{
    $cartItems = Cart::instance('cart')->content();
    return view('cart',compact('cartItems'));
}

public function addToCart(Request $request)
{
    Cart::instance('cart')->add($request->id,$request->name,$request->quantity,$request->price)->associate('App\Models\Product');        
    session()->flash('success', 'Product is Added to Cart Successfully !');        
    return redirect()->back();
} 

public function increase_item_quantity($rowId)
{
    $product = Cart::instance('cart')->get($rowId);
    $qty = $product->qty + 1;
    Cart::instance('cart')->update($rowId,$qty);
    return redirect()->back();
}
public function reduce_item_quantity($rowId){
    $product = Cart::instance('cart')->get($rowId);
    $qty = $product->qty - 1;
    Cart::instance('cart')->update($rowId,$qty);
    return redirect()->back();
}

public function remove_item_from_cart($rowId)
{
    Cart::instance('cart')->remove($rowId);
    return redirect()->back();
}

public function empty_cart()
{
    Cart::instance('cart')->destroy();
    return redirect()->back();
}

public function apply_coupon_code(Request $request)
{
    $request->validate([
        'coupon_code' => 'required|string|max:50'
    ]);

    $coupon = Coupon::where('code', $request->coupon_code)
        ->where('expiry_date', '>=', Carbon::today())
        ->first();

    if (!$coupon) {
        return back()->with('error', 'Invalid or expired coupon code!');
    }

    $cartSubtotal = Cart::instance('cart')->subtotal();
    
    if ($coupon->cart_value > $cartSubtotal) {
        return back()->with('error', 
            'Minimum cart value for this coupon is '.format_currency($coupon->cart_value)
        );
    }

    // Check if coupon is already applied
    if (Session::has('coupon') && Session::get('coupon')['code'] === $coupon->code) {
        return back()->with('info', 'This coupon is already applied!');
    }

    // Store coupon in session
    session()->put('coupon', [
        'id' => $coupon->id, // Store ID for validation
        'code' => $coupon->code,
        'type' => $coupon->type,
        'value' => $coupon->value,
        'cart_value' => $coupon->cart_value,
        'applied_at' => now() // Track when coupon was applied
    ]);

    $this->calculateDiscounts();

    return back()->with('status', 'Coupon applied successfully!');
}

protected function calculateDiscounts()
{
    $cart = Cart::instance('cart');
    $subtotal = $cart->subtotal();
    $discount = 0;

    if (Session::has('coupon')) {
        $coupon = Session::get('coupon');
        
        if ($coupon['type'] === 'fixed') {
            $discount = min($coupon['value'], $subtotal);
        } elseif ($coupon['type'] === 'percent') {
            $discount = $subtotal * ($coupon['value'] / 100);
        }

        // Ensure discount doesn't exceed cart value
        $discount = min($discount, $subtotal);
    }

    // Store discount in session
    Session::put('cart_discount', $discount);
    
    // Update cart instance if needed
    // $cart->setDiscount($discount);
}

public function remove_coupon_code(Request $request)
{
    if (!Session::has('coupon')) {
        return back()->with('error', 'No coupon applied!');
    }

    // Additional validation - verify coupon still exists
    $coupon = Coupon::find(Session::get('coupon')['id']);
    if (!$coupon) {
        Session::forget('coupon');
        return back()->with('error', 'This coupon no longer exists!');
    }

    Session::forget('coupon');
    $this->calculateDiscounts();

    return back()->with('status', 'Coupon removed successfully!');
}
public function checkout()
{
    if(!Auth::check())
    {
        return redirect()->route("login");
    }
    $address = Address::where('user_id',Auth::user()->id)->where('isdefault',1)->first();              
    return view('checkout',compact("address"));
}

public function place_order(Request $request)
{
    $user_id = Auth::user()->id;
    $address = Address::where('user_id',$user_id)->where('isdefault',true)->first();
    if(!$address)
    {
        $request->validate([                
            'name' => 'required|max:100',
            'phone' => 'required|numeric|digits:10',
            'zip' => 'required|numeric|digits:6',
            'state' => 'required',
            'city' => 'required',
            'address' => 'required',
            'locality' => 'required',
            'landmark' => 'required'           
        ]);
        $address = new Address();    
        $address->user_id = $user_id;    
        $address->name = $request->name;
        $address->phone = $request->phone;
        $address->zip = $request->zip;
        $address->state = $request->state;
        $address->city = $request->city;
        $address->address = $request->address;
        $address->locality = $request->locality;
        $address->landmark = $request->landmark;
        $address->country = '';
        $address->isdefault = true;
        $address->save();
    }
    $this->setAmountForCheckout();
    $order = new Order();
    $order->user_id = $user_id;
    $order->subtotal = session()->get('checkout')['subtotal'];
    $order->discount = session()->get('checkout')['discount'];
    $order->tax = session()->get('checkout')['tax'];
    $order->total = session()->get('checkout')['total'];
    $order->name = $address->name;
    $order->phone = $address->phone;
    $order->locality = $address->locality;
    $order->address = $address->address;
    $order->city = $address->city;
    $order->state = $address->state;
    $order->country = $address->country;
    $order->landmark = $address->landmark;
    $order->zip = $address->zip;
    $order->save();                
    foreach(Cart::instance('cart')->content() as $item)
    {
        $orderitem = new OrderItem();
        $orderitem->product_id = $item->id;
        $orderitem->order_id = $order->id;
        $orderitem->price = $item->price;
        $orderitem->quantity = $item->qty;
        $orderitem->save();                   
    }
    $transaction = new Transaction();
    $transaction->user_id = $user_id;
    $transaction->order_id = $order->id;
    $transaction->mode = $request->mode;
    $transaction->status = "pending";
    $transaction->save();
    
    Cart::instance('cart')->destroy();
    session()->forget('checkout');
    session()->forget('coupon');
    session()->forget('discounts');
    session()->put('order_id',$order->id);

    return redirect()->route('cart.confirmation',compact('order'));
}

public function setAmountForCheckout()
{ 
    if(!Cart::instance('cart')->count() > 0)
    {
        session()->forget('checkout');
        return;
    }    
    if(session()->has('coupon'))
    {
        session()->put('checkout',[
            'discount' => session()->get('discounts')['discount'],
            'subtotal' =>  session()->get('discounts')['subtotal'],
            'tax' =>  session()->get('discounts')['tax'],
            'total' =>  session()->get('discounts')['total']
        ]);
    }
    else
    {
        session()->put('checkout',[
            'discount' => 0,
            'subtotal' => Cart::instance('cart')->subtotal(),
            'tax' => Cart::instance('cart')->tax(),
            'total' => Cart::instance('cart')->total()
        ]);
    }
}


public function confirmation()
{
  
 
    return view('confirmation');



}

}