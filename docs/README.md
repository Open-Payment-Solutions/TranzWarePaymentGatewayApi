# Integration guide — 1.5.3

This guide uses the shortened API names introduced in 1.3.0 on `main`.
For the original names, use [edition 1.2.3](../README.md#using-the-previous-edition-123).
When upgrading, follow the [migration instructions](../CHANGELOG.md#130).

## Install

```sh
composer require open-payment-solutions/tranzware-payment-gateway:"~1.5.3"
```

Use PHP 8.2 or newer and enable cURL, JSON, mbstring, and SimpleXML. Obtain your merchant ID,
gateway URL, signed client certificate, private key, and optional key password
from your bank. Configure the public HTTPS return URLs for your application.

The flow is: create an order on your server, save its gateway identifiers,
redirect the customer to `PaymentUrl`, parse the return, and query the gateway
from your server to verify the outcome.

## Configure the gateway

In the following examples, save this as your application's `gateway.php` beside
its `vendor/` directory. Set the environment variables for your deployment.
Keep certificate/key files outside the public web directory.

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use OpenPaymentSolutions\TranzWarePaymentGateway\RequestFactory;

$requiredEnv = static function ($name) {
    $value = getenv($name);
    if ($value === false || $value === '') {
        throw new RuntimeException('Missing configuration: ' . $name);
    }
    return $value;
};

$returnBase = rtrim($requiredEnv('TWPG_RETURN_BASE_URL'), '/');
$factory = new RequestFactory(
    $requiredEnv('TWPG_GATEWAY_URL'),
    $requiredEnv('TWPG_MERCHANT_ID'),
    $returnBase . '/order_approved.php',
    $returnBase . '/order_declined.php',
    $returnBase . '/order_canceled.php',
    'EN'
);
$factory->setCertificate(
    $requiredEnv('TWPG_CERT_FILE'),
    $requiredEnv('TWPG_KEY_FILE'),
    getenv('TWPG_KEY_PASSWORD') ?: ''
)->enableSSLVerification();

return $factory;
```

`TWPG_GATEWAY_URL` is the bank's XML API endpoint. `TWPG_RETURN_BASE_URL` is your
application's public HTTPS base URL, such as `https://shop.example/payments`.
Certificate and key settings are filesystem paths. TLS verification should stay
enabled; configure the bank's trusted CA in your PHP/cURL environment if needed.
The supplied certificate files in `samples/` are placeholders.

## Create an order

For a purchase, save this as `create.php` beside `gateway.php`:

```php
<?php
use OpenPaymentSolutions\TranzWarePaymentGateway\CurrencyCodes;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidOrderType;

$factory = require __DIR__ . '/gateway.php';

// Take amount and currency from your own server-side checkout record.
try {
    $result = $factory->createOrderPurchaseRequest(
        100,
        CurrencyCodes::USD,
        'Checkout 123'
    )->execute();
} catch (InvalidOrderType $e) {
    http_response_code(400);
    exit($e->getMessage());
} catch (Exception $e) {
    http_response_code(502);
    exit($e->getMessage());
}

if ($result->getHttpStatus() !== 200 || !$result->success()) {
    http_response_code(502);
    exit('Gateway rejected order creation.');
}

$order = $result->getData();
// Persist $order->orderId and $order->sessionId with your checkout here,
// before redirecting. Keep the session ID on your server.
header('Location: ' . $order->paymentUrl, true, 303);
exit;
```

The amount is sent unchanged: confirm the required units with your bank.
Currency codes can be numeric strings or `CurrencyCodes` enum cases (USD, EUR,
AZN, RUB, and SDG). Use `CurrencyCodes::fromString('usd')` to parse a name or
numeric code, and `CurrencyCodes::USD->value` for the gateway string. Request
values are XML-escaped automatically; pass original text without pre-escaping it.

On successful creation, `getData()` returns `Data\OrderData` with readonly
`url`, `orderId`, `sessionId`, and `paymentUrl` properties, plus `__unparsed`. `success()` here means the gateway accepted the **creation** request
(gateway status `00`); it does not mean the customer has paid.

For preauthorization, use the same configured factory:

```php
$result = $factory->createOrderPreAuthRequest(
    100,
    CurrencyCodes::USD,
    'Reservation 123'
)->execute();
```

You can also select the order type explicitly:

```php
use OpenPaymentSolutions\TranzWarePaymentGateway\OrderTypes;

$result = $factory->createOrderRequest(
    100, CurrencyCodes::USD, 'Checkout 123', OrderTypes::PURCHASE
)->execute();
```

The default is `OrderTypes::PURCHASE`. `OrderTypes::fromString('pre_auth')`
returns `OrderTypes::PRE_AUTH`; unknown values throw
`OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidOrderType`.
Preauthorization reserves funds; this
package does not provide capture, refund, or cancellation request methods.

## Handle the return and check status

`HandlerFactory` creates a parser for the gateway's `xmlmsg` request parameter.
It accepts raw XML or base64-encoded XML and returns a `Data\OrderCallbackData`
object with `dateTime`, `orderId`, `amount`, `currency`, `orderStatus`, and
`xmlMessage` properties. `xmlMessage` contains the original request value.
Additional gateway fields are retained in `__unparsed`. Missing or empty
messages throw `Exceptions\EmptyXMLMessage`; invalid XML, incomplete callbacks,
and non-string messages throw `Exceptions\InvalidXMLMessage`. The handler reads `$_REQUEST`; it parses data without authenticating the
sender or verifying a payment.

For example, use this return handler for approved, declined, and canceled returns:

```php
<?php
use OpenPaymentSolutions\TranzWarePaymentGateway\HandlerFactory;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\EmptyXMLMessage;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidXMLMessage;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidOrderId;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidSessionId;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\OrderStatusRetrieveException;

$factory = require __DIR__ . '/gateway.php';

try {
    $callback = (new HandlerFactory())->createOrderCallbackHandler()->handle();
} catch (EmptyXMLMessage | InvalidXMLMessage $e) {
    http_response_code(400);
    exit($e->getMessage());
}

// Application-specific: load the checkout saved during order creation.
// It must belong to your application and contain OrderId and SessionId.
$savedOrder = findCheckoutByGatewayOrderId($callback->orderId);
if (!$savedOrder || $savedOrder['OrderId'] !== $callback->orderId) {
    http_response_code(404);
    exit('Unknown order.');
}

try {
    $result = $factory->createOrderStatusRequest(
        $savedOrder['OrderId'],
        $savedOrder['SessionId']
    )->execute();
} catch (InvalidOrderId | InvalidSessionId $e) {
    http_response_code(400);
    exit($e->getMessage());
} catch (OrderStatusRetrieveException $e) {
    http_response_code(502);
    exit($e->getMessage());
}

$data = $result->getData();
if ($result->getHttpStatus() === 200
    && $result->getStatus() === '00'
    && isset($data->orderStatus)
    && $data->orderStatus === 'APPROVED') {
    // Application-specific: reconcile the saved amount/currency and order type,
    // then record the verified outcome idempotently in your database.
    // For a preauthorization, APPROVED is authorization, not capture.
    echo 'Order approved by gateway.';
} else {
    echo 'Payment has not been confirmed.';
}
```

Implement `findCheckoutByGatewayOrderId()` using your application's database.
The callback's order status, amount, and currency are untrusted input. Use your
saved order/session pair for the server-side status request; never fulfill an
order solely because the browser visited the approval URL. The status result
does not supply a verified amount or currency, so reconcile those through your
bank's supported process when needed.

You can run `createOrderStatusRequest($orderId, $sessionId)->execute()` at any time
using the stored pair, independently of a callback. Null, empty, or whitespace-only
IDs throw `Exceptions\InvalidOrderId` or `Exceptions\InvalidSessionId` when the
request is created. `execute()` throws `Exceptions\OrderStatusRetrieveException`
for transport failures, unsuccessful HTTP or gateway status, malformed XML, or a
missing order status. The original exception is available through `getPrevious()`.
A retrieved `DECLINED` or `CANCELED` status remains a valid result.
`getStatus()` is the gateway request-processing code, while
`getData()->orderStatus` is the order outcome. For status results, `success()`
checks `APPROVED`; inspect HTTP status and gateway status as shown above as well.
Other outcomes must not be treated as confirmed payment.

## Results, errors, and debugging

| Operation | Result type in `Requests\` | Data object in `Data\` |
| --- | --- | --- |
| Create purchase/preauthorization | `OrderRequestResult` | `OrderData`: `url`, `orderId`, `sessionId`, `paymentUrl` |
| Query order status | `OrderStatusRequestResult` | `OrderStatusData`: `orderId`, `orderStatus` |

Both result classes implement `Requests\RequestResultInterface` and expose
`getHttpStatus()`, `getStatus()`, `getData()`, and `success()`.
`OrderStatusRequestResult` also exposes `getDetails()` and may throw for missing
order information. Directly constructed results for empty responses return data
objects with null fields, an empty `__unparsed` array, and a null gateway status;
status request `execute()` throws instead. Always check HTTP status and data before
using them. A network failure may have HTTP
status `0`. Malformed or unexpected gateway XML throws `UnexpectedValueException`
when parsing a result directly, wrapped in `OrderStatusRetrieveException` by
status request `execute()`. Handle failures at your application's boundary. `getResponseBody()` returns the
raw response string, or `false` when transport fails. Status responses map the
gateway's `OrderID` to the `orderId` property.

Data objects expose readonly camelCase properties. Use property access such as
`$result->getData()->orderId` instead of array indexing. `getData()` always returns
a data object; missing order fields are null, including rejected creation results.
All data objects include `__unparsed`, an array that defaults to `[]`.

Extra XML fields retain their original names and nesting. For example, an extra
`ApprovalCode` under `Response/Order` is available at
`$data->__unparsed['Response']['Order']['ApprovalCode']`. Repeated elements become
lists. XML attributes use `@attributes`, and mixed text uses `#text`. Namespaced
extra fields retain their prefix, or use `{namespace-uri}name` for a default namespace.

`HTTPClientResult::getInfo()` returns `Data\HTTPInfo` with `httpCode`, `url`,
`contentType`, and `totalTime` properties. Other cURL values retain their original
keys inside `__unparsed`. Raw response bodies remain available through `getOutput()`.

For temporary diagnostics, set a writable file before creating a request:

```php
$factory->setDebugFile('/private/path/twpg-debug.log');
```

Debug output includes request/response bodies and session identifiers. Restrict
file access and remove logs when finished; each request opens the file in `w+`
mode, overwriting it. Leave debugging off during normal operation.

For additional examples, see [samples](../samples). For the gateway protocol,
see the [integration manual in Russian](Integration_Instruction_TWEC_PG.pdf).
