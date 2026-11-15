> [!WARNING]
> **ALERT: Breaking changes after 1.2.3.** Versions greater than `1.2.3`
> (`> 1.2.3`, not including `1.2.3`) are not backward compatible with the
> original API. For existing integrations, use the strict requirement
> **`"1.2.3"`** until you have migrated and tested your application.
>
> `composer require open-payment-solutions/tranzware-payment-gateway:"1.2.3"`

# TranzWare Payment Gateway (TWPG) API

PHP library for creating orders, checking order status, and parsing callbacks from
Compass Plus TranzWare Payment Gateway (also known as TWEC PG).

The current edition is **1.5.1** on `main`. The original edition is preserved on
`v1.2.3` (formerly `master`), with release tag `1.2.3`.

## Installation

For an existing integration using the original API, pin **exactly 1.2.3**:

```sh
composer require open-payment-solutions/tranzware-payment-gateway:"1.2.3"
```

The corresponding `composer.json` requirement is:

```json
{
  "require": {
    "open-payment-solutions/tranzware-payment-gateway": "1.2.3"
  }
}
```

Keep the version free of `^` or `~` prefixes when you require the original API.

For a new integration, or after migrating to the current API:

```sh
composer require open-payment-solutions/tranzware-payment-gateway:"~1.5.1"
```

The current edition requires **PHP 8.2 or newer**, cURL, JSON, mbstring, and SimpleXML.
Your bank must provide the gateway URL, merchant ID, and client certificate and
private key. PHP_CodeSniffer and PHPUnit are development dependencies.

## Integration

Start with the [integration guide](docs/README.md), including examples for:

- [Configuring the gateway and certificates](docs/README.md#configure-the-gateway).
- [Creating purchase and preauthorization orders](docs/README.md#create-an-order).
- [Parsing callbacks and verifying order status](docs/README.md#handle-the-return-and-check-status).

See also the [sample scripts](samples), the
[gateway integration manual in Russian](docs/Integration_Instruction_TWEC_PG.pdf),
and the [changelog](CHANGELOG.md).

## Changes after 1.2.3

| Version | Changes to account for when upgrading |
| --- | --- |
| 1.3.0 | Shorter public class, interface, and trait names; the old names have no aliases. |
| 1.3.1 | GitHub Actions compatibility fixes. |
| 1.4.0 | PHP 8.1 minimum, native types, backed enums, typed exceptions, automatic XML escaping, and result parsing fixes. |
| 1.4.1 | PSR-12 formatting and coding standard checks across source, tests, and samples. |
| 1.5.0 | Typed data objects with camelCase properties and `__unparsed`; complete factory interface and PHPDoc. |
| 1.5.1 | PHP 8.2 minimum, refreshed dependencies and test tooling, and explicit compatibility guidance. |

### Public API names

All classes, interfaces, and the trait previously prefixed with
`TranzWarePaymentGateway` now use short names in the same namespaces:

```php
// 1.2.3
use OpenPaymentSolutions\TranzWarePaymentGateway\TranzWarePaymentGatewayHandlerFactory;
use OpenPaymentSolutions\TranzWarePaymentGateway\TranzWarePaymentGatewayRequestFactoryInterface;

// Current edition
use OpenPaymentSolutions\TranzWarePaymentGateway\HandlerFactory;
use OpenPaymentSolutions\TranzWarePaymentGateway\RequestFactoryInterface;
```

Update imports, fully qualified names, type hints, inheritance, trait references,
and dependency injection configuration. There are no compatibility aliases for
the old names. See the [complete rename table](CHANGELOG.md#130), then run
`composer dump-autoload` after updating your application.

The request factory interface includes purchase, preauthorization, status,
certificate, SSL verification, and debug configuration methods. Native parameter
and return types also apply to custom implementations and subclasses.

### Order types and currencies

`OrderTypes` and `CurrencyCodes` are string-backed enums. Request methods accept
enum cases or strings. Both enums provide `sanitizeValue()`, `isValid()`, and
`fromString()`; `fromString()` returns an enum case.

```php
use OpenPaymentSolutions\TranzWarePaymentGateway\CurrencyCodes;
use OpenPaymentSolutions\TranzWarePaymentGateway\OrderTypes;

OrderTypes::fromString('pre_auth'); // OrderTypes::PRE_AUTH
CurrencyCodes::fromString('usd');  // CurrencyCodes::USD
CurrencyCodes::USD->value;         // '840'
```

Unknown order types throw `Exceptions\InvalidOrderType`. Omitting the order type
when creating a request still selects `Purchase`. Unknown currencies passed to
`CurrencyCodes::fromString()` throw `ValueError`. Numeric currency strings outside
the enum remain accepted by request methods.

### Structured result data

Gateway data uses objects under `OpenPaymentSolutions\TranzWarePaymentGateway\Data`
with readonly camelCase properties:

| Method | Returned data object | Properties |
| --- | --- | --- |
| `OrderRequestResult::getData()` | `OrderData` | `url`, `orderId`, `sessionId`, `paymentUrl` |
| `OrderStatusRequestResult::getData()` | `OrderStatusData` | `orderId`, `orderStatus` |
| `OrderCallbackHandler::handle()` | `OrderCallbackData` | `dateTime`, `orderId`, `amount`, `currency`, `orderStatus`, `xmlMessage` |
| `HTTPClientResult::getInfo()` | `HTTPInfo` | `httpCode`, `url`, `contentType`, `totalTime` |

Replace array access such as `$data['OrderId']` with `$data->orderId`. `getData()`
always returns an object; missing order fields are `null`, including empty or
rejected creation results. `getResponseBody()` returns the raw response string or
`false` for a transport failure. Gateway `OrderID` is mapped to `orderId`.

Every data object also includes **`__unparsed`**, an array that defaults to `[]`.
Extra gateway fields retain their original names and nesting, including repeated
XML elements, namespaces, attributes (`@attributes`), and mixed text (`#text`).
Other cURL metadata retains its original keys in `HTTPInfo::__unparsed`.

```php
$order = $result->getData();
$order->orderId;
$order->paymentUrl;
$order->__unparsed['Response']['Order']['ApprovalCode'] ?? null;
```

### Exceptions and request values

The library's custom exceptions live under
`OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions`:

| Exception | Trigger |
| --- | --- |
| `InvalidOrderType` | An unsupported order type is parsed or supplied to a request. |
| `InvalidOrderId` | A status request is created with a null, empty, or whitespace-only order ID. |
| `InvalidSessionId` | A status request is created with a null, empty, or whitespace-only session ID. |
| `EmptyXMLMessage` | A callback message is missing, null, or an empty string. |
| `InvalidXMLMessage` | A callback message is not a string, is invalid XML, or lacks required fields. |
| `OrderStatusRetrieveException` | Status retrieval fails through transport, HTTP or gateway status, malformed XML, or missing order status. |

`OrderStatusRetrieveException::getPrevious()` exposes the underlying cause.
Malformed responses parsed directly by result classes throw
`UnexpectedValueException`. Samples catch input errors with HTTP 400 and retrieval
failures with HTTP 502, displaying `$e->getMessage()`.

Request values are XML-escaped automatically. Pass original text without
pre-escaping it. The amount is sent unchanged; confirm the required units with
your bank.

Successful order creation means the gateway accepted the creation request; it
does not confirm payment. A parsed callback also does not authenticate its sender.
Verify the order through a server-side status request using your saved order and
session IDs. A retrieved `DECLINED` or `CANCELED` outcome is a valid status result,
not a retrieval exception. Preauthorization reserves funds; capture, refunds, and
cancellation requests are not implemented by this package.

## Tests and development

```sh
composer install
composer lint
composer test
```

`composer format` applies automatic PSR-12 fixes. `composer lint` also validates
PHPDoc across source, tests, and samples. Dependency versions should be checked
against current stable releases while respecting the PHP minimum. PHPUnit data
providers use `#[DataProvider(...)]` attributes; PHPDoc retains their parameter
and return documentation.

Tests simulate transport calls and do not contact a payment gateway. For coverage,
use `composer test:coverage` with Xdebug or PCOV enabled. CI runs
coding standards, tests, and coverage on PHP 8.2 through 8.5. Version tags trigger
a release workflow that validates the package before publishing GitHub release notes.

## Using the previous edition (1.2.3)

Keep the strict Composer requirement shown above and use the original imports:

```php
use OpenPaymentSolutions\TranzWarePaymentGateway\TranzWarePaymentGatewayRequestFactory;
use OpenPaymentSolutions\TranzWarePaymentGateway\TranzWarePaymentGatewayHandlerFactory;
```

To work with the preserved source and examples:

```sh
git clone --branch v1.2.3 --single-branch https://github.com/Open-Payment-Solutions/TranzWarePaymentGatewayApi.git
cd TranzWarePaymentGatewayApi
composer install
```

The `v1.2.3` branch preserves the former `master` checkout, including README
updates made after the `1.2.3` tag. For the exact released source, use tag `1.2.3`
or the strict Composer requirement. Existing clones can run `git fetch origin`
and then `git switch v1.2.3` for the previous edition or `git switch main` for the
current edition.

## Forks

[Laravel integration](https://github.com/Ab-Alselwi/tranzware-payment-gateway)
by [Abdulfattah Alselwi](https://github.com/Ab-Alselwi).
