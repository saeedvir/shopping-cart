# Security Policy

## Reporting a Vulnerability

If you discover a security vulnerability within this package, please send an email to saeed.es91@gmail.com. All security vulnerabilities will be promptly addressed.

Please do not publicly disclose the issue until it has been addressed.

## Security Best Practices

When using this package:

1. **Validate All Input**: Always validate quantities, prices, and other user input before adding to cart
2. **Fetch Prices from Database**: Never trust prices from client-side or user input
3. **Validate Stock**: Check product availability before checkout
4. **Use CSRF Protection**: Ensure all cart modification routes are CSRF protected
5. **Sanitize Attributes**: Validate and sanitize custom attributes
6. **Rate Limiting**: Implement rate limiting on cart operations
7. **Session Security**: Use secure session configuration
8. **Database Security**: Use parameterized queries (built-in with Eloquent)

## Example: Secure Cart Addition

```php
public function add(Request $request, $productId)
{
    // Validate input
    $validated = $request->validate([
        'quantity' => 'required|integer|min:1|max:100',
        'attributes' => 'nullable|array',
        'attributes.*' => 'string|max:255',
    ]);

    // Fetch product from database (don't trust client)
    $product = Product::findOrFail($productId);

    // Validate stock
    if ($product->stock < $validated['quantity']) {
        abort(400, 'Insufficient stock');
    }

    // Add to cart with database price
    Cart::add($product, $validated['quantity'], $validated['attributes'] ?? []);

    return response()->json(['success' => true]);
}
```

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Known Security Considerations

- Cart data in session storage is encrypted by Laravel
- Database storage uses prepared statements
- No SQL injection vulnerabilities
- XSS protection: sanitize output in views
- CSRF protection required for modification routes
