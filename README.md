# TranzWare Payment Gateway (TWPG) API

PHP library for creating orders, checking order status, and parsing callbacks from
the Compass Plus TranzWare Payment Gateway (also known as TWEC PG).

The new edition is **1.4.1** on `main`. The previous edition is preserved on the
`v1.2.3` branch (formerly `master`); its latest release tag is `1.2.3`.

## Installation

```sh
composer require open-payment-solutions/tranzware-payment-gateway:"~1.4.1"
```

PHP 8.1 or newer with cURL, JSON, and SimpleXML is required. Your bank must provide the gateway
URL, merchant ID, and client certificate/private key.

## Integration

Start with the [integration guide](docs/README.md), including examples for:

- [Configuring the gateway and certificates](docs/README.md#configure-the-gateway).
- [Creating purchase and preauthorization orders](docs/README.md#create-an-order).
- [Parsing callbacks and verifying order status](docs/README.md#handle-the-return-and-check-status).

See also the [sample scripts](samples), the
[gateway integration manual in Russian](docs/Integration_Instruction_TWEC_PG.pdf),
and the [changelog](CHANGELOG.md).

## Breaking changes in 1.3.0

**Upgrading from 1.2.3 requires code changes**, despite the minor version number.
All classes, interfaces, and the trait prefixed with `TranzWarePaymentGateway`
now use short names. Their namespaces stay the same. For example:

```php
// 1.2.3
use OpenPaymentSolutions\TranzWarePaymentGateway\TranzWarePaymentGatewayHandlerFactory;
use OpenPaymentSolutions\TranzWarePaymentGateway\TranzWarePaymentGatewayRequestFactoryInterface;

// 1.3.0
use OpenPaymentSolutions\TranzWarePaymentGateway\HandlerFactory;
use OpenPaymentSolutions\TranzWarePaymentGateway\RequestFactoryInterface;
```

The old names and filenames have been removed; there are no compatibility aliases.
Update imports, fully qualified names, type hints, inheritance, trait references,
and dependency injection configuration, then regenerate Composer's autoloader.
Method names, arguments, and result array keys are unchanged.
`CurrencyCodes` and `OrderTypes` keep their names.

See the [complete rename table and migration steps](CHANGELOG.md#130).

## PHP 8.1 API updates

`OrderTypes` and `CurrencyCodes` are now string-backed enums. Request methods
accept enum cases or strings. Use `->value` when you need the gateway string.
Both enums provide `sanitizeValue()`, `isValid()`, and `fromString()`;
`fromString()` returns an enum case.

```php
OrderTypes::fromString('pre_auth'); // OrderTypes::PRE_AUTH
CurrencyCodes::fromString('usd');  // CurrencyCodes::USD
CurrencyCodes::USD->value;         // '840'
```

Unknown order types throw `OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidOrderType`. Omitting the order type when
creating a request still selects `Purchase`. Unknown values passed to
`CurrencyCodes::fromString()` throw `ValueError`. Numeric currency strings
outside the enum remain accepted by request methods.

Parameters, properties, interfaces, and return values now have native types;
custom implementations and subclasses must use compatible signatures.
Request values are XML-escaped by the library. Pass original text without
pre-escaping it. Malformed gateway responses throw `UnexpectedValueException`
when parsing results directly. Status request `execute()` wraps retrieval failures
in `Exceptions\OrderStatusRetrieveException`; missing identifiers throw
`Exceptions\InvalidOrderId` or `Exceptions\InvalidSessionId` during request creation.

## Tests

```sh
composer install
composer lint
composer test
phpdbg -qrr vendor/bin/phpunit --coverage-text
```

Coding standards use PSR-12 across source, tests, and samples. Run
`composer format` to apply automatic fixes.

Coverage requires phpdbg, Xdebug, or PCOV. With Xdebug or PCOV enabled,
`composer test:coverage` also prints the report. Tests simulate transport calls
and do not contact a payment gateway.

## Using the previous edition (1.2.3)

To keep the original API names, pin the released version:

```sh
composer require open-payment-solutions/tranzware-payment-gateway:"1.2.3"
```

Use its original imports, for example:

```php
use OpenPaymentSolutions\TranzWarePaymentGateway\TranzWarePaymentGatewayRequestFactory;
use OpenPaymentSolutions\TranzWarePaymentGateway\TranzWarePaymentGatewayHandlerFactory;
```

To work with the previous edition's source and samples:

```sh
git clone --branch v1.2.3 --single-branch https://github.com/Open-Payment-Solutions/TranzWarePaymentGatewayApi.git
cd TranzWarePaymentGatewayApi
composer install
```

The `v1.2.3` branch preserves the former `master` checkout, including README
updates made after the `1.2.3` release tag. Use the exact Composer version above
for the tagged release. Existing clones can run `git fetch origin` followed by
`git switch v1.2.3` for the previous edition or `git switch main` for the new one.

## Forks

[Laravel integration](https://github.com/Ab-Alselwi/tranzware-payment-gateway)
by [Abdulfattah Alselwi](https://github.com/Ab-Alselwi).
