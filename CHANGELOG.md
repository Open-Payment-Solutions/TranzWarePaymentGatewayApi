# Changelog

## 1.3.0

The new edition lives on `main`. Version is recorded in [VERSION](VERSION);
Composer release versions are derived from Git tags.

### Breaking changes

Removed the redundant `TranzWarePaymentGateway` prefix from every prefixed class,
interface, and trait, and renamed the corresponding PHP files. **The old names
are no longer available**, with no compatibility aliases. This is a breaking API
change from 1.2.3, even though the version increases to 1.3.0.

All names below remain under `OpenPaymentSolutions\TranzWarePaymentGateway`.
The `Requests\` and `Handlers\` subnamespaces also remain unchanged.

| Namespace suffix | 1.2.3 name | 1.3.0 name |
| --- | --- | --- |
| (root) | `TranzWarePaymentGatewayHandlerFactory` | `HandlerFactory` |
| (root) | `TranzWarePaymentGatewayHandlerFactoryInterface` | `HandlerFactoryInterface` |
| (root) | `TranzWarePaymentGatewayRequestFactory` | `RequestFactory` |
| (root) | `TranzWarePaymentGatewayRequestFactoryInterface` | `RequestFactoryInterface` |
| (root) | `TranzWarePaymentGatewayUrlProviderInterface` | `UrlProviderInterface` |
| (root) | `TranzWarePaymentGatewayUrls` | `Urls` |
| `Handlers\` | `TranzWarePaymentGatewayHandlerInterface` | `HandlerInterface` |
| `Handlers\` | `TranzWarePaymentGatewayOrderCallbackHandler` | `OrderCallbackHandler` |
| `Requests\` | `TranzWarePaymentGatewayHTTPClient` | `HTTPClient` |
| `Requests\` | `TranzWarePaymentGatewayHTTPClientInterface` | `HTTPClientInterface` |
| `Requests\` | `TranzWarePaymentGatewayHTTPClientResult` | `HTTPClientResult` |
| `Requests\` | `TranzWarePaymentGatewayHTTPClientResultInterface` | `HTTPClientResultInterface` |
| `Requests\` | `TranzWarePaymentGatewayOrderRequest` | `OrderRequest` |
| `Requests\` | `TranzWarePaymentGatewayOrderRequestResult` | `OrderRequestResult` |
| `Requests\` | `TranzWarePaymentGatewayOrderStatusRequest` | `OrderStatusRequest` |
| `Requests\` | `TranzWarePaymentGatewayOrderStatusRequestResult` | `OrderStatusRequestResult` |
| `Requests\` | `TranzWarePaymentGatewayRequestInterface` | `RequestInterface` |
| `Requests\` | `TranzWarePaymentGatewayRequestResultInterface` | `RequestResultInterface` |
| `Requests\` | `TranzWarePaymentGatewayRequestSettings` (trait) | `RequestSettings` (trait) |

### Migrating from 1.2.3

1. Update imports and all references to the names in the table, including
   `implements`, `extends`, trait `use` statements, type hints, class strings, and
   container bindings. Update any manual includes to the new filenames.
2. Update the package constraint:

   ```sh
   composer require open-payment-solutions/tranzware-payment-gateway:"~1.3.0"
   composer dump-autoload
   ```

3. Clear application/container caches and test order creation, return handling,
   and status verification against your bank's test gateway before deployment.

Public method names and signatures, gateway XML templates, callback fields, and
result array keys are unchanged. `CurrencyCodes` and `OrderTypes` are unchanged.

### Added and updated

- [Integration guide](docs/README.md) with certificate configuration, purchase,
  preauthorization, callback parsing, and status verification examples.
- Updated sample scripts and their Composer constraint for the shortened API.
- Samples keep TLS verification enabled and use the correct purchase helper name.
- Preserved the previous edition on `v1.2.3` (renamed from `master`); development
  and contribution instructions now use `main`.
- Removed redundant `final` modifiers from private methods to avoid PHP 8 warnings.

## 1.2.3 — 2022-12-08

Previous edition, released under the `1.2.3` tag. The `v1.2.3` branch preserves
the former `master`, including subsequent README updates.

- Uses the original `TranzWarePaymentGateway…` class, interface, and trait names.
- Supports purchase and preauthorization order creation, order status requests,
  and XML/base64 callback parsing. Gateway requests use client certificates.
- Rejects empty order/session IDs when creating an order status request.
- Handles status responses with missing order fields by raising an exception
  with gateway details when available.
- Updated dependency constraints for case conversion, PHP_CodeSniffer, and PHPUnit.

To stay on this edition:

```sh
composer require open-payment-solutions/tranzware-payment-gateway:"1.2.3"
```

Keep the original long imports. See [using the previous edition](README.md#using-the-previous-edition-123)
for source checkout instructions. Earlier releases are available in Git history.
