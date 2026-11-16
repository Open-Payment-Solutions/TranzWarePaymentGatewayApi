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

The current edition is **1.7.1** on `main`. The original edition is preserved on
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
composer require open-payment-solutions/tranzware-payment-gateway:"~1.7.1"
```

The current edition requires **PHP 8.2 or newer**, JSON, mbstring, and SimpleXML.
Enable cURL for the preferred HTTP/HTTPS transport. Without cURL, enable
`allow_url_fopen` for PHP streams and OpenSSL for HTTPS.
Your bank must provide the gateway URL, merchant ID, and client certificate and
private key. PHP_CodeSniffer and PHPUnit are development dependencies.

## Integration

Start with the [integration guide](docs/README.md), including examples for:

- [Configuring the gateway and certificates](docs/README.md#configure-the-gateway).
- [Creating purchase and preauthorization orders](docs/README.md#create-an-order).
- [Parsing callbacks and verifying order status](docs/README.md#handle-the-return-and-check-status).

See also the [example scripts](examples), the
[gateway integration manual in Russian](docs/Integration_Instruction_TWEC_PG.pdf),
and the [changelog](CHANGELOG.md).

## Changes after 1.2.3

| Version | Changes to account for when upgrading |
| --- | --- |
| 1.3.0 | Shorter public class, interface, and trait names; the old names have no aliases. |
| 1.3.1 | GitHub Actions compatibility fixes. |
| 1.4.0 | PHP 8.1 minimum, native types, backed enums, typed exceptions, automatic XML escaping, and result parsing fixes. |
| 1.4.1 | PSR-12 formatting and coding standard checks across source, tests, and examples. |
| 1.5.0 | Typed data objects with camelCase properties and `__unparsed`; complete factory interface and PHPDoc. |
| 1.5.1 | PHP 8.2 minimum, refreshed dependencies and test tooling, and explicit compatibility guidance. |
| 1.5.2 | Track `composer.lock` for reproducible development and CI dependency installs. |
| 1.5.3 | Harden CI permissions and release source validation; pin patched actions to commit SHAs. |
| 1.5.4 | Require a tracked, current Composer lockfile in CI and reject dependency file changes during installation. |
| 1.5.5 | Add a security policy with private vulnerability reporting and security update guidance. |
| 1.6.0 | Replace the case-converter dependency with an autoloaded PascalCase utility; preserve order type normalization. |
| 1.7.1 | Prefer cURL with checked stream fallback, default strictSSL to false, add configurable versioned User-Agent, rename examples, and add Docker integration CI. |

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

### Utility functions

Composer automatically loads utility functions from `src/helpers.php`. The
`toPascalCase()` function converts snake, kebab, space, dot, camel, and Pascal
case strings using UTF-8. Invalid UTF-8 produces an empty string.

```php
use function OpenPaymentSolutions\TranzWarePaymentGateway\toPascalCase;

toPascalCase('pre_auth'); // 'PreAuth'
```

Order type normalization uses this helper without changing its accepted names
or validation rules. The package has no third-party runtime packages; mbstring
remains required for Unicode case conversion.

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
Transport diagnostics are retained in `HTTPInfo::__unparsed`. Streams provide
`response_headers`, `timed_out`, and `error`; cURL retains its connection and
timing fields along with `error`.

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
`UnexpectedValueException`. Examples catch input errors with HTTP 400 and retrieval
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

## Security

See the [security policy](SECURITY.md) for security update guidance and private
vulnerability reporting instructions. Please do not report vulnerabilities in
public issues.

## Tests and development

```sh
composer install
composer lint
composer test
```

`HTTPClient` uses `TransportFactory` to prefer `Transports\CurlTransport` when
the cURL extension, required functions, and requested protocol are available.
Otherwise it selects `Transports\HTTPTransport` or `Transports\HTTPSTransport`
after checking stream functions, URL wrappers, `allow_url_fopen`, and the TCP/TLS
features needed by the URL. If neither backend is usable, it throws
`Exceptions\TransportNotAvailable` with the reasons for each backend.

Both backends POST XML and support client certificates, encrypted private keys,
and custom CA trust for HTTPS. **`strictSSL` defaults to `false`.** Pass `true` or
call `enableSSLVerification()` to enable server certificate and hostname checks.
Redirects are not followed; HTTP error responses retain their bodies. Stream reads
use a 10-second timeout; cURL uses a 10-second connection timeout. The factory
selects a backend before sending; failed requests are not retried through another
transport.

Requests send `User-Agent: OpenPaymentSolutions/TranzWarePaymentGateway/v1.7.1`
by default. The version comes from the package's `VERSION` file. Override it
before creating requests, or pass `null` to restore the package default:

```php
$factory->setUserAgent('MyShop/2.0');
$request = $factory->createOrderPurchaseRequest(100, '840');
$request->setUserAgent('OrderWorker/2.0');
$factory->setUserAgent(null);
```

`HTTPClient` also accepts an optional fifth constructor argument, `userAgent`,
and provides `setUserAgent()`. Direct transports and `TransportFactory::create()`
accept optional overrides too. Empty values and CR, LF, or NUL bytes are rejected.
Custom implementations of `RequestFactoryInterface`, `RequestInterface`, and
`HTTPClientInterface` must provide the new `setUserAgent()` method; the HTTP
client interface also adds the optional constructor argument.

`composer test` uses in-memory responses without network requests or Docker.
Run the real HTTP, HTTPS, and mutual TLS checks separately:

```sh
sh tests/integration/transports/run.sh --matrix
```

The [standalone Docker suite](tests/integration/transports/README.md) runs a
mock server and PHP clients over a private Docker network. It generates and
verifies fresh server and client certificates before each run, then removes its
containers and certificate volume. GitHub Actions runs four combinations of
cURL/streams and strictSSL enabled/disabled as separate integration jobs on pushes
and pull requests. To select a single configuration:

```sh
TWPG_TEST_WITH_CURL=1 TWPG_TEST_STRICT_SSL=1 sh tests/integration/transports/run.sh
```

`composer format` applies automatic PSR-12 fixes. `composer lint` also validates
PHPDoc across source, tests, and examples. Dependency versions should be checked
against current stable releases while respecting the PHP minimum. PHPUnit data
providers use `#[DataProvider(...)]` attributes; PHPDoc retains their parameter
and return documentation.

The committed `composer.lock` pins the development and CI dependencies. Run
`composer install` to reproduce them; use `composer update` when deliberately
refreshing dependency versions. Applications consuming this library use their
own lock file.

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
updates made after the `1.2.3` tag. The original `1.2.3` tag stays unchanged;
compatibility warnings are provided on the legacy branch and in its
[release notes](https://github.com/Open-Payment-Solutions/TranzWarePaymentGatewayApi/releases/tag/1.2.3). For the exact released source, use tag `1.2.3`
or the strict Composer requirement. Existing clones can run `git fetch origin`
and then `git switch v1.2.3` for the previous edition or `git switch main` for the
current edition.

## Forks

[Laravel integration](https://github.com/Ab-Alselwi/tranzware-payment-gateway)
by [Abdulfattah Alselwi](https://github.com/Ab-Alselwi).
