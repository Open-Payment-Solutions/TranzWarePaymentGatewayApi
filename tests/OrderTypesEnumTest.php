<?php

namespace tests;

use OpenPaymentSolutions\TranzWarePaymentGateway\OrderTypes;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class OrderTypesEnumTest extends TestCase
{
    /**
     * Parse order types into enum cases.
     *
     * @param string $input
     * @param OrderTypes $expected
     *
     * @see    self::orderTypes()
     * @return void
     */
    #[DataProvider('orderTypes')]
    public function testFromStringReturnsEnumCase(string $input, OrderTypes $expected): void
    {
        $this->assertSame($expected, OrderTypes::fromString($input));
        $this->assertTrue(OrderTypes::isValid($expected));
        $this->assertSame($expected, OrderTypes::fromString($expected));
        $this->assertSame($expected, OrderTypes::from($expected->value));
    }

    /**
     * Provide order type strings and their expected enum cases.
     *
     * @return array
     */
    public static function orderTypes(): array
    {
        return [['Purchase', OrderTypes::PURCHASE], ['purchase', OrderTypes::PURCHASE],
            ['PURCHASE', OrderTypes::PURCHASE], ['PRE_AUTH', OrderTypes::PRE_AUTH],
            ['preAuth', OrderTypes::PRE_AUTH], ['pre-auth', OrderTypes::PRE_AUTH],
            ['pre auth', OrderTypes::PRE_AUTH], ['pre.auth', OrderTypes::PRE_AUTH],
            ['__PRE__AUTH__', OrderTypes::PRE_AUTH]];
    }

    /**
     * Reject unknown order types with a specific exception.
     *
     * @param string $input
     *
     * @see    self::invalidTypes()
     * @return void
     */
    #[DataProvider('invalidTypes')]
    public function testUnknownOrderTypeThrowsSpecificException(string $input): void
    {
        $this->expectException(\OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidOrderType::class);
        $this->expectExceptionMessage('Invalid order type: ' . $input);
        OrderTypes::fromString($input);
    }

    /**
     * Provide unsupported order type strings.
     *
     * @return array
     */
    public static function invalidTypes(): array
    {
        return [[''], ['Unknown'], ['?!'], ['PREAUTH'], ['PreAUTH'], [' PreAuth'],
            ['-pre-auth'], ['.pre.auth'], ['pré_auth']];
    }

    /**
     * Sanitize invalid character encoding to an empty string.
     *
     * @return void
     */
    public function testInvalidEncodingIsSanitizedToEmptyString(): void
    {
        $this->assertSame('', OrderTypes::sanitizeValue("Pre\xffAuth"));
    }

    /**
     * Require exact gateway names when validating order types.
     *
     * @return void
     */
    public function testValidationRequiresExactGatewayName(): void
    {
        $this->assertTrue(OrderTypes::isValid('Purchase'));
        $this->assertTrue(OrderTypes::isValid('PreAuth'));
        $this->assertFalse(OrderTypes::isValid('pre_auth'));
        $this->assertFalse(OrderTypes::isValid(''));
        $this->assertSame('', OrderTypes::sanitizeValue('_'));
    }
}
