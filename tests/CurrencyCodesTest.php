<?php

namespace tests;

use OpenPaymentSolutions\TranzWarePaymentGateway\CurrencyCodes;
use PHPUnit\Framework\TestCase;

final class CurrencyCodesTest extends TestCase
{
    /** @dataProvider currencies */
    public function testCurrencyCanBeParsedByNameOrCode(CurrencyCodes $currency): void
    {
        foreach (
            [
            $currency->value,
            $currency->name,
            strtolower($currency->name),
            ' ' . $currency->name . ' ',
            ] as $input
        ) {
            $this->assertSame($currency->value, CurrencyCodes::sanitizeValue($input));
            $this->assertTrue(CurrencyCodes::isValid($input));
            $this->assertSame($currency, CurrencyCodes::fromString($input));
        }
        $this->assertTrue(CurrencyCodes::isValid($currency));
        $this->assertSame($currency, CurrencyCodes::fromString($currency));
    }

    public static function currencies(): array
    {
        return array_map(fn (CurrencyCodes $currency): array => [$currency], CurrencyCodes::cases());
    }

    /** @dataProvider invalidCurrencies */
    public function testInvalidCurrencyIsRejected(string $currency): void
    {
        $this->assertFalse(CurrencyCodes::isValid($currency));
        $this->expectException(\ValueError::class);
        CurrencyCodes::fromString($currency);
    }

    public static function invalidCurrencies(): array
    {
        return [[''], [' '], ['GBP'], ['000'], ['usd!'], ['8 40']];
    }
}
