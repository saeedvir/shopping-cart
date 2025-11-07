<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Saeedvir\ShoppingCart\Facades\Cart;
use App\Models\Product;

class CartController extends Controller
{
    /**
     * Display the cart.
     */
    public function index()
    {
        $cart = Cart::toArray();
        
        return view('cart.index', compact('cart'));
    }

    /**
     * Add item to cart.
     */
    public function add(Request $request, $productId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'attributes' => 'nullable|array',
        ]);

        $product = Product::findOrFail($productId);

        // Validate stock
        if ($product->stock < $request->quantity) {
            return back()->with('error', 'Insufficient stock available');
        }

        Cart::add($product, $request->quantity, $request->attributes ?? []);

        return back()->with('success', 'Product added to cart');
    }

    /**
     * Update cart item.
     */
    public function update(Request $request, $itemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $item = Cart::get($itemId);
        
        if (!$item) {
            return back()->with('error', 'Item not found in cart');
        }

        Cart::update($itemId, ['quantity' => $request->quantity]);

        return back()->with('success', 'Cart updated');
    }

    /**
     * Remove item from cart.
     */
    public function remove($itemId)
    {
        Cart::remove($itemId);

        return back()->with('success', 'Item removed from cart');
    }

    /**
     * Clear cart.
     */
    public function clear()
    {
        Cart::clear();

        return back()->with('success', 'Cart cleared');
    }

    /**
     * Apply coupon.
     */
    public function applyCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string',
        ]);

        try {
            Cart::applyCoupon($request->coupon_code, function ($code, $cart) {
                $coupon = \App\Models\Coupon::where('code', $code)
                    ->where('is_active', true)
                    ->where('starts_at', '<=', now())
                    ->where('expires_at', '>=', now())
                    ->first();

                if (!$coupon) {
                    return false;
                }

                if ($cart->subtotal() < $coupon->minimum_purchase) {
                    return false;
                }

                if ($coupon->type === 'percentage') {
                    $cart->condition('coupon', 'discount', $coupon->value, 'percentage');
                } else {
                    $cart->condition('coupon', 'discount', $coupon->value, 'fixed');
                }

                return true;
            });

            return back()->with('success', 'Coupon applied successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Invalid or expired coupon code');
        }
    }

    /**
     * Remove coupon.
     */
    public function removeCoupon()
    {
        Cart::removeCondition('coupon');

        return back()->with('success', 'Coupon removed');
    }
}
