# Changelog

## 1.5.0

### Breaking changes

- Return typed data objects from order results, callback handlers, and HTTP metadata
  accessors. Use readonly camelCase properties instead of array keys. Every data
  object includes `__unparsed` for extra gateway fields, preserving XML nesting,
  repeated elements, attributes, namespaces, and mixed text. Missing order fields
  are null, including empty or rejected creation results.
### Documentation and tooling

- Complete the request factory interface and add missing native void return types
  to test methods.
- Complete PHPDoc descriptions, parameter and return annotations, and sample file
  descriptions. Include PHPDoc checks in the PHPCS ruleset.

## 1.4.1

- Apply PSR-12 coding standards across source files, tests, and samples, fixing
  PHPCS errors and warnings while preserving existing comments.
- Add a project PHPCS ruleset, `composer lint` and `composer format` commands,
  and coding standard checks in CI.
- Separate the sample test runner's debug stream function into its own fixture.

## 1.4.0

### Breaking changes

- Require PHP 8.1 or newer and add native parameter, property, and return types.
  Custom implementations and subclasses must use compatible signatures.
- Convert `OrderTypes` and `CurrencyCodes` to string-backed enums with
  `sanitizeValue()`, `isValid()`, and `fromString()` helpers. Request methods
  accept enum cases or strings; use `->value` for the gateway string.
  `OrderTypes::fromString()` now returns an enum case and throws
  `Exceptions\InvalidOrderType` for unknown values instead of selecting Purchase.
- Callback handlers now throw `Exceptions\EmptyXMLMessage` for missing or empty
  messages and `Exceptions\InvalidXMLMessage` for invalid messages, instead of
  returning null.
- Status request creation rejects null, empty, or whitespace-only identifiers
  with `Exceptions\InvalidOrderId` or `Exceptions\InvalidSessionId`.
  `OrderStatusRequest::execute()` throws `Exceptions\OrderStatusRetrieveException`
  for retrieval failures and preserves the original cause. Valid declined or
  canceled outcomes remain ordinary results.
- XML-escape request values automatically. Applications must pass original text
  without pre-escaping it. Malformed gateway responses now throw exceptions.

### Fixes and tests

- Return the raw response from `getResponseBody()` and map gateway `OrderID` to
  the status result's `OrderId` key.
- Handle empty transport bodies and debug file failures without PHP warnings,
  and close debug file descriptors.
- Add unit and sample-script tests covering exceptions, SSL options, request
  XML, callbacks, results, and HTTP error responses without gateway calls.
- Add coverage reporting and configure CI for PHP 8.1 through 8.5.
- Update PHPUnit to 9.6 and require PHP_CodeSniffer 3.13.6 or newer.
- Update the integration guide and samples for enums and typed exceptions.

## 1.3.1

- Fixed GitHub Actions failures caused by the retired `actions/cache@v2`, upgrading
  to `actions/cache@v4`.
- Updated `actions/checkout` from v2 to v4.
- Updated the version file, installation instructions, and sample dependency
  constraint to 1.3.1.

No additional API changes from 1.3.0. The [1.3.0 breaking changes](#130) still
apply when upgrading from 1.2.3.

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
