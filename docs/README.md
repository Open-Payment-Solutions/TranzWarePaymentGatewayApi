# Integration guide — 1.3.0

This guide uses the shortened API names introduced in 1.3.0 on `main`.
For the original names, use [edition 1.2.3](../README.md#using-the-previous-edition-123).
When upgrading, follow the [migration instructions](../CHANGELOG.md#130).

## Install

```sh
composer require open-payment-solutions/tranzware-payment-gateway:"~1.3.0"
```

Enable PHP's cURL, JSON, and SimpleXML extensions. Obtain your merchant ID,
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

$factory = require __DIR__ . '/gateway.php';

// Take amount and currency from your own server-side checkout record.
$result = $factory->createOrderPurchaseRequest(
    100,
    CurrencyCodes::USD,
    'Checkout 123'
)->execute();

if ($result->getHttpStatus() !== 200 || !$result->success()) {
    throw new RuntimeException('Gateway rejected order creation.');
}

$order = $result->getData();
// Persist $order['OrderId'] and $order['SessionId'] with your checkout here,
// before redirecting. Keep SessionId on your server.
header('Location: ' . $order['PaymentUrl'], true, 303);
exit;
```

The amount is sent unchanged: confirm the required units with your bank.
Currency codes are numeric strings; `CurrencyCodes` includes USD, EUR, AZN, RUB,
and SDG. Supply trusted values: request templates substitute strings directly
into XML, so text containing XML metacharacters must be escaped by your
application (for example with `htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8')`).

On successful creation, `getData()` contains `URL`, `OrderId`, `SessionId`, and
`PaymentUrl`. `success()` here means the gateway accepted the **creation** request
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

The default is `OrderTypes::PURCHASE`. Preauthorization reserves funds; this
package does not provide capture, refund, or cancellation request methods.

## Handle the return and check status

`HandlerFactory` creates a parser for the gateway's `xmlmsg` request parameter.
It accepts raw XML or base64-encoded XML and returns `DateTime`, `OrderId`,
`Amount`, `Currency`, `OrderStatus`, and the original `xmlmsg`. Invalid XML returns
`null`. The handler reads `$_REQUEST`; it parses data without authenticating the
sender or verifying a payment.

For example, use this return handler for approved, declined, and canceled returns:

```php
<?php
use OpenPaymentSolutions\TranzWarePaymentGateway\HandlerFactory;

$factory = require __DIR__ . '/gateway.php';

if (!isset($_REQUEST['xmlmsg']) || !is_string($_REQUEST['xmlmsg'])) {
    http_response_code(400);
    exit('Missing callback data.');
}

$callback = (new HandlerFactory())->createOrderCallbackHandler()->handle();
if ($callback === null || !is_string($callback['OrderId'])) {
    http_response_code(400);
    exit('Invalid callback data.');
}

// Application-specific: load the checkout saved during order creation.
// It must belong to your application and contain OrderId and SessionId.
$savedOrder = findCheckoutByGatewayOrderId($callback['OrderId']);
if (!$savedOrder || $savedOrder['OrderId'] !== $callback['OrderId']) {
    http_response_code(404);
    exit('Unknown order.');
}

try {
    $result = $factory->createOrderStatusRequest(
        $savedOrder['OrderId'],
        $savedOrder['SessionId']
    )->execute();
} catch (Exception $e) {
    http_response_code(502);
    exit('Unable to verify payment.');
}

$data = $result->getData();
if ($result->getHttpStatus() === 200
    && $result->getStatus() === '00'
    && isset($data['OrderStatus'])
    && $data['OrderStatus'] === 'APPROVED') {
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
using the stored pair, independently of a callback. Empty IDs throw an exception.
`getStatus()` is the gateway request-processing code, while
`getData()['OrderStatus']` is the order outcome. For status results, `success()`
checks `APPROVED`; inspect HTTP status and gateway status as shown above as well.
Other outcomes must not be treated as confirmed payment.

## Results, errors, and debugging

| Operation | Result type in `Requests\` | Data keys |
| --- | --- | --- |
| Create purchase/preauthorization | `OrderRequestResult` | `URL`, `OrderId`, `SessionId`, `PaymentUrl` on success |
| Query order status | `OrderStatusRequestResult` | `OrderId`, `OrderStatus` when returned by the gateway |

Both result classes implement `Requests\RequestResultInterface` and expose
`getHttpStatus()`, `getStatus()`, `getData()`, and `success()`.
`OrderStatusRequestResult` also exposes `getDetails()` and may throw for missing
order information. Empty responses have empty data and a null gateway status;
check HTTP status and data before using them. A network failure may have HTTP
status `0`. Malformed or unexpected gateway XML can produce PHP warnings or
exceptions: handle failures at your application's boundary.

Existing limitations carried over from 1.2.3: `getResponseBody()` currently
returns `null` in both request result classes, and status responses using
`OrderID` may yield a null `OrderId` because of the parser's casing. Keep using
your persisted order ID. These behaviors are unchanged by the naming migration.

For temporary diagnostics, set a writable file before creating a request:

```php
$factory->setDebugFile('/private/path/twpg-debug.log');
```

Debug output includes request/response bodies and session identifiers. Restrict
file access and remove logs when finished; each request opens the file in `w+`
mode, overwriting it. Leave debugging off during normal operation.

For additional examples, see [samples](../samples). For the gateway protocol,
see the [integration manual in Russian](Integration_Instruction_TWEC_PG.pdf).
