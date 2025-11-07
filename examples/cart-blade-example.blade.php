{{-- resources/views/cart/index.blade.php --}}

@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Shopping Cart</h1>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if(count($cart['items']) > 0)
        <div class="bg-white rounded-lg shadow">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left">Product</th>
                        <th class="px-6 py-3 text-left">Price</th>
                        <th class="px-6 py-3 text-left">Quantity</th>
                        <th class="px-6 py-3 text-left">Total</th>
                        <th class="px-6 py-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cart['items'] as $item)
                        <tr class="border-b">
                            <td class="px-6 py-4">
                                <div>
                                    <h3 class="font-semibold">{{ $item['name'] }}</h3>
                                    @if(!empty($item['attributes']))
                                        <div class="text-sm text-gray-600">
                                            @foreach($item['attributes'] as $key => $value)
                                                <span>{{ ucfirst($key) }}: {{ $value }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">${{ number_format($item['price'], 2) }}</td>
                            <td class="px-6 py-4">
                                <form action="{{ route('cart.update', $item['id']) }}" method="POST" class="flex items-center">
                                    @csrf
                                    @method('PUT')
                                    <input type="number" name="quantity" value="{{ $item['quantity'] }}" 
                                           min="1" class="w-20 border rounded px-2 py-1">
                                    <button type="submit" class="ml-2 text-blue-600 hover:text-blue-800">
                                        Update
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 font-semibold">${{ number_format($item['total'], 2) }}</td>
                            <td class="px-6 py-4">
                                <form action="{{ route('cart.remove', $item['id']) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800">
                                        Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="p-6 bg-gray-50">
                <div class="max-w-md ml-auto">
                    {{-- Coupon Form --}}
                    <form action="{{ route('cart.apply-coupon') }}" method="POST" class="mb-4">
                        @csrf
                        <div class="flex gap-2">
                            <input type="text" name="coupon_code" placeholder="Coupon Code" 
                                   class="flex-1 border rounded px-3 py-2">
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                                Apply
                            </button>
                        </div>
                    </form>

                    {{-- Cart Summary --}}
                    <div class="space-y-2 text-lg">
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span>${{ number_format($cart['subtotal'], 2) }}</span>
                        </div>

                        @if($cart['tax'] > 0)
                            <div class="flex justify-between">
                                <span>Tax:</span>
                                <span>${{ number_format($cart['tax'], 2) }}</span>
                            </div>
                        @endif

                        @if($cart['discount'] > 0)
                            <div class="flex justify-between text-green-600">
                                <span>Discount:</span>
                                <span>-${{ number_format($cart['discount'], 2) }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between font-bold text-xl pt-2 border-t">
                            <span>Total:</span>
                            <span>${{ number_format($cart['total'], 2) }}</span>
                        </div>
                    </div>

                    <div class="mt-6 flex gap-3">
                        <form action="{{ route('cart.clear') }}" method="POST" class="flex-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full bg-gray-500 text-white px-6 py-3 rounded hover:bg-gray-600">
                                Clear Cart
                            </button>
                        </form>
                        <a href="{{ route('checkout') }}" class="flex-1 bg-green-600 text-white px-6 py-3 rounded hover:bg-green-700 text-center">
                            Checkout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <p class="text-gray-600 mb-4">Your cart is empty</p>
            <a href="{{ route('products.index') }}" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                Continue Shopping
            </a>
        </div>
    @endif
</div>
@endsection
