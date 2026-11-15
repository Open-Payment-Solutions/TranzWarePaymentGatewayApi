<?php

namespace tests;

use OpenPaymentSolutions\TranzWarePaymentGateway\OrderTypes;
use PHPUnit\Framework\TestCase;

final class OrderTypesEnumTest extends TestCase
{
    /** @dataProvider orderTypes */
    public function testFromStringReturnsEnumCase(string $input, OrderTypes $expected): void
    {
        $this->assertSame($expected, OrderTypes::fromString($input));
        $this->assertTrue(OrderTypes::isValid($expected));
        $this->assertSame($expected, OrderTypes::fromString($expected));
        $this->assertSame($expected, OrderTypes::from($expected->value));
    }

    public static function orderTypes(): array
    {
        return [['Purchase', OrderTypes::PURCHASE], ['PRE_AUTH', OrderTypes::PRE_AUTH],
            ['preAuth', OrderTypes::PRE_AUTH]];
    }

    /** @dataProvider invalidTypes */
    public function testUnknownOrderTypeThrowsSpecificException(string $input): void
    {
        $this->expectException(\OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidOrderType::class);
        $this->expectExceptionMessage('Invalid order type: ' . $input);
        OrderTypes::fromString($input);
    }

    public static function invalidTypes(): array
    {
        return [[''], ['Unknown'], ['?!']];
    }

    public function testInvalidEncodingIsSanitizedToEmptyString(): void
    {
        $this->assertSame('', OrderTypes::sanitizeValue("Pre\xffAuth"));
    }

    public function testValidationRequiresExactGatewayName(): void
    {
        $this->assertTrue(OrderTypes::isValid('Purchase'));
        $this->assertTrue(OrderTypes::isValid('PreAuth'));
        $this->assertFalse(OrderTypes::isValid('pre_auth'));
        $this->assertFalse(OrderTypes::isValid(''));
        $this->assertSame('', OrderTypes::sanitizeValue('_'));
    }
}
