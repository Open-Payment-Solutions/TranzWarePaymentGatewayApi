# Migration: old (1.2.3) → latest (1.5.x+)

This guide compares the original **1.2.3** API with the typed API available from
**1.5.x** onward. The current release is **1.7.2**. Examples below target that
release; additions from 1.6 and 1.7 are identified separately.

The original source remains available at tag `1.2.3` and branch `v1.2.3`.
Later releases do not provide aliases for the old class names. Migrate and test
your application before changing its production dependency.

## Compare the editions

| Area | Old: 1.2.3 | Latest: 1.5.x+ |
| --- | --- | --- |
| PHP requirement | No PHP version constraint in the root `composer.json`. | PHP 8.2 or newer from 1.5.1 onward; 1.5.0 required PHP 8.1. |
| Public types | Names such as `TranzWarePaymentGatewayRequestFactory`. | Short names such as `RequestFactory`, with native parameter and return types. |
| Order and currency constants | Strings such as `OrderTypes::PURCHASE` and `CurrencyCodes::USD`. | String-backed enum cases; use `->value` when a scalar string is needed. |
| Unknown order type | `OrderTypes::fromString()` silently falls back to `Purchase`. | Throws `Exceptions\InvalidOrderType`. |
| Parsed order/status data | Arrays, with `[]` or `null` possible for creation failures. | Readonly data objects with camelCase properties and `__unparsed`. |
| Callback data | An array, or `null` when XML cannot be parsed. | `Data\OrderCallbackData`; missing or invalid input throws a dedicated exception. |
| HTTP information | `getInfo()` returns a cURL information array. | Returns `Data\HTTPInfo`. |
| Request values | Values are substituted into XML without automatic escaping. | XML escaping is automatic. Pass original text. |
| Raw response access | `getResponseBody()` has no return statement and returns `null`. | Returns the response string, or `false` for a transport failure. |

Enable JSON, mbstring, and SimpleXML on the current PHP runtime. Transport
requirements changed again in 1.7; see [later additions](#later-additions-in-16-and-17).

## Rename imports and custom implementations

Namespaces stay under `OpenPaymentSolutions\TranzWarePaymentGateway`.
The table's namespace suffix is appended to that root.

| Namespace suffix | Old: 1.2.3 | Latest |
| --- | --- | --- |
| Root | `TranzWarePaymentGatewayRequestFactory` | `RequestFactory` |
| Root | `TranzWarePaymentGatewayRequestFactoryInterface` | `RequestFactoryInterface` |
| Root | `TranzWarePaymentGatewayHandlerFactory` | `HandlerFactory` |
| Root | `TranzWarePaymentGatewayHandlerFactoryInterface` | `HandlerFactoryInterface` |
| Root | `TranzWarePaymentGatewayUrls` | `Urls` |
| Root | `TranzWarePaymentGatewayUrlProviderInterface` | `UrlProviderInterface` |
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
| `Requests\` | `TranzWarePaymentGatewayRequestSettings` | `RequestSettings` (trait) |

Update imports, fully qualified names, type hints, inheritance, trait references,
dependency injection configuration, and mocks. `OrderTypes` and `CurrencyCodes`
keep their names but become enums.

Custom interfaces and subclasses must match the current native method signatures.
For example, request methods accept `OrderTypes|string` and `CurrencyCodes|string`;
`HTTPClientResultInterface::getInfo()` returns `Data\HTTPInfo`. Implement the
factory's purchase, preauthorization, status, certificate, SSL, and debug methods.
From 1.7.1, also implement `setUserAgent()` on `RequestFactoryInterface`,
`Requests\RequestInterface`, and `Requests\HTTPClientInterface`. The HTTP client
constructor gains an optional fifth `?string $userAgent = null` argument.

## Create orders and read their data

Both examples use your configured gateway URLs, merchant ID, and certificate paths.
Keep the amount in the units required by your bank.

Old (1.2.3):

```php
use OpenPaymentSolutions\TranzWarePaymentGateway\TranzWarePaymentGatewayRequestFactory;
use OpenPaymentSolutions\TranzWarePaymentGateway\CurrencyCodes;
use OpenPaymentSolutions\TranzWarePaymentGateway\OrderTypes;

$factory = new TranzWarePaymentGatewayRequestFactory(
    $gatewayUrl, $merchantId, $approvedUrl, $declinedUrl, $canceledUrl
);
$factory->setCertificate($certFile, $keyFile, $keyPass)->enableSSLVerification();
$result = $factory->createOrderRequest(100, CurrencyCodes::USD, 'Checkout 123', OrderTypes::PURCHASE)->execute();
if ($result->getHttpStatus() === 200 && $result->success()) {
    $order = $result->getData();
    $paymentUrl = $order['PaymentUrl'];
    $orderId = $order['OrderId'];
    $sessionId = $order['SessionId'];
}
```

Latest (1.5.x+):

```php
use OpenPaymentSolutions\TranzWarePaymentGateway\RequestFactory;
use OpenPaymentSolutions\TranzWarePaymentGateway\CurrencyCodes;
use OpenPaymentSolutions\TranzWarePaymentGateway\OrderTypes;

$factory = new RequestFactory(
    $gatewayUrl, $merchantId, $approvedUrl, $declinedUrl, $canceledUrl
);
$factory->setCertificate($certFile, $keyFile, $keyPass)->enableSSLVerification();
$result = $factory->createOrderRequest(100, CurrencyCodes::USD, 'Checkout 123', OrderTypes::PURCHASE)->execute();
if ($result->getHttpStatus() === 200 && $result->success()) {
    $order = $result->getData();
    $paymentUrl = $order->paymentUrl;
    $orderId = $order->orderId;
    $sessionId = $order->sessionId;
}
```

Save the order and session IDs server-side before redirecting to the payment URL.
Successful order creation means the gateway accepted the creation request; it
does not confirm that payment was completed.

### Replace array keys with properties

| Result | Old array key | Latest property |
| --- | --- | --- |
| Created order | `URL` | `url` |
| Created order | `OrderId` | `orderId` |
| Created order | `SessionId` | `sessionId` |
| Created order | `PaymentUrl` | `paymentUrl` |
| Order status | `OrderId` | `orderId` |
| Order status | `OrderStatus` | `orderStatus` |
| Callback | `DateTime` | `dateTime` |
| Callback | `OrderId` | `orderId` |
| Callback | `Amount` | `amount` |
| Callback | `Currency` | `currency` |
| Callback | `OrderStatus` | `orderStatus` |
| Callback | `xmlmsg` | `xmlMessage` |

`getData()` always returns an object. Creation/status fields can be `null` when
missing. Do not use `empty($result->getData())` to detect a failed creation;
check the HTTP status and `success()` instead. Malformed responses can throw
`UnexpectedValueException`. Data properties are readonly, so copy values into
your application's model instead of modifying the returned object.

All data objects provide `__unparsed`, an array of additional gateway fields.
For example, an extra approval code remains accessible at
`$order->__unparsed['Response']['Order']['ApprovalCode']`.
If your application serializes these objects to JSON, account for the new
property names and `__unparsed` in its public response format.

The old status result looked up `OrderId` rather than gateway `OrderID`, so its
order ID could be `null`. The current parser maps `OrderID` to `orderId`.
Continue using the saved order ID to correlate requests.

## Use enum values where strings are required

Old (1.2.3):

```php
$purchase = OrderTypes::PURCHASE; // 'Purchase'
$currency = CurrencyCodes::USD; // '840'
$type = OrderTypes::fromString('pre_auth'); // 'PreAuth'
```

Latest (1.5.x+, using the imports above):

```php
$purchase = OrderTypes::PURCHASE; // Enum case
$currency = CurrencyCodes::USD; // Enum case
$type = OrderTypes::fromString('pre_auth'); // OrderTypes::PRE_AUTH
$purchaseValue = $purchase->value; // 'Purchase'
$currencyValue = $currency->value; // '840'
```

Request methods accept enum cases or strings, so passing `CurrencyCodes::USD`
or `OrderTypes::PURCHASE` directly still works. Use `->value` for database values,
string comparisons, or external APIs that require scalar strings.

Unknown order types now throw `Exceptions\InvalidOrderType` instead of silently
selecting a purchase. `CurrencyCodes::fromString('usd')` returns the USD enum;
unknown values throw `ValueError`. Numeric currency strings outside the enum
remain accepted by request methods. `OrderTypes::isValid()` requires an enum
case or an exact gateway name; `fromString()` normalizes names such as `pre_auth`.

## Handle callbacks and status errors

Old (1.2.3):

```php
use OpenPaymentSolutions\TranzWarePaymentGateway\TranzWarePaymentGatewayHandlerFactory;

$callback = (new TranzWarePaymentGatewayHandlerFactory())->createOrderCallbackHandler()->handle();
if ($callback !== null) {
    $callbackOrderId = $callback['OrderId'];
}
```

Latest (1.5.x+):

```php
use OpenPaymentSolutions\TranzWarePaymentGateway\HandlerFactory;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\EmptyXMLMessage;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidXMLMessage;

try {
    $callback = (new HandlerFactory())->createOrderCallbackHandler()->handle();
    $callbackOrderId = $callback->orderId;
} catch (EmptyXMLMessage | InvalidXMLMessage $error) {
    http_response_code(400);
}
```

Parsing a callback does not authenticate its sender. Retrieve order status
server-side using your saved order and session IDs before fulfilling an order.

| Condition | Latest exception or result |
| --- | --- |
| Unknown order type | `Exceptions\InvalidOrderType` |
| Null, empty, or whitespace-only order ID | `Exceptions\InvalidOrderId` |
| Null, empty, or whitespace-only session ID | `Exceptions\InvalidSessionId` |
| Missing or empty callback message | `Exceptions\EmptyXMLMessage` |
| Non-string, malformed, or incomplete callback XML | `Exceptions\InvalidXMLMessage` |
| Failed status retrieval | `Exceptions\OrderStatusRetrieveException`; inspect `getPrevious()` for the cause. |
| Valid `DECLINED` or `CANCELED` status | A status result; `success()` is `false`. |

Status requests use `$factory->createOrderStatusRequest($savedOrderId, $savedSessionId)->execute()`.
Read `$result->getData()->orderStatus` and `$result->getData()->orderId` instead
of the old array keys. Existing catch blocks for generic exceptions may need
separate handling for invalid input and retrieval failures.

## Update XML values and HTTP information

Pass original request values such as `A&B <invoice>` to the current factory.
If your application previously XML-escaped values, remove that escaping to avoid
double-encoding. The library escapes values when it builds request XML.

For an HTTP result, change `$httpResult->getInfo()['http_code']` to
`$httpResult->getInfo()->httpCode`. The other normalized fields are `url`,
`contentType`, and `totalTime`. Additional transport fields are in `__unparsed`.
Keep raw response bodies out of public error messages; they can include payment
session identifiers.

## Later additions in 1.6 and 1.7

These changes are additional to the 1.5.x typed data API:

- **1.6.0:** The case-converter dependency is replaced by the Composer-autoloaded
  `toPascalCase()` helper in `src/helpers.php`. Existing order type normalization
  is preserved.
- **1.7.1:** `TransportFactory` prefers cURL when its functions and the requested
  protocol are available. Otherwise, HTTP/HTTPS streams require enabled stream
  functions, the matching URL wrapper, and `allow_url_fopen`; HTTPS streams also
  require OpenSSL. Missing capabilities raise `Exceptions\TransportNotAvailable`.
  Status requests wrap that exception in `OrderStatusRetrieveException`.
- **1.7.1:** `strictSSL` defaults to `false`. Keep an explicit
  `$factory->enableSSLVerification()` call when your integration requires server
  certificate and hostname verification. In 1.2.3, `setCertificate()` enabled
  verification by default when no mode had been selected.
- **1.7.1:** The default User-Agent is
  `OpenPaymentSolutions/TranzWarePaymentGateway/v<version>`. Override it with
  `$factory->setUserAgent('MyShop/2.0')`, or pass `null` to restore the default.
  Request and HTTP client instances support overrides too.
- **1.7.1:** `samples/` is renamed to [`examples/`](examples). Update copied paths
  and callback URLs if your deployment uses that directory name.

## Upgrade and verify

After updating your application code and PHP environment, update the dependency
in your application's checkout:

```sh
composer require open-payment-solutions/tranzware-payment-gateway:"~1.7.2"
composer dump-autoload
```

Run your application's tests for creation success and failure, preauthorization,
callback validation, status retrieval, declined/canceled outcomes, and transport
errors. Verify certificate settings and callbacks against your bank's sandbox.
The library's unit and Docker integration suites use simulated gateway responses;
they do not verify a live bank integration.

See the [integration guide](docs/README.md) for complete current examples and the
[changelog](CHANGELOG.md) for changes by release.
