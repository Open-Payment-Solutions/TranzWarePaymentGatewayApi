<?php

namespace tests;

use OpenPaymentSolutions\TranzWarePaymentGateway\OrderTypes;
use PHPUnit\Framework\TestCase;

final class OrderTypesTest extends TestCase
{
    /**
     * Sanitize unsupported characters to an empty order type.
     *
     * @return void
     */
    public function testOrderTypeSanitizerReturnsEmptyString(): void
    {
        $input = '  _-,./?\!@#$%^&*()+= ';
        $actual = OrderTypes::sanitizeValue($input);
        $this->assertEmpty($actual);
    }

    /**
     * Normalize uppercase snake case order types.
     *
     * @return void
     */
    public function testOrderTypeSanitizerSnakeCase(): void
    {
        $input = 'PRE_AUTH';
        $expected = 'PreAuth';
        $actual = OrderTypes::sanitizeValue($input);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Normalize camel case order types.
     *
     * @return void
     */
    public function testOrderTypeSanitizerCamelCaseLowerCaseFirst(): void
    {
        $input = 'preAuth';
        $expected = 'PreAuth';
        $actual = OrderTypes::sanitizeValue($input);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Normalize lowercase snake case order types.
     *
     * @return void
     */
    public function testOrderTypeSanitizerSnakeCaseAllLowerCase(): void
    {
        $input = 'pre_auth';
        $expected = 'PreAuth';
        $actual = OrderTypes::sanitizeValue($input);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Reject unsupported order type names.
     *
     * @return void
     */
    public function testValidationInvalidType(): void
    {
        $input = 'FakeAuth';
        $actual = OrderTypes::isValid($input);
        $this->assertFalse($actual);
    }

    /**
     * Accept a supported gateway order type name.
     *
     * @return void
     */
    public function testValidationValidType(): void
    {
        $input = 'PreAuth';
        $actual = OrderTypes::isValid($input);
        $this->assertTrue($actual);
    }

    /**
     * Parse uppercase snake case order types.
     *
     * @return void
     */
    public function testFromStringSnakeCase(): void
    {
        $input = 'PRE_AUTH';
        $expected = 'PreAuth';
        $actual = OrderTypes::fromString($input)->value;
        $this->assertEquals($expected, $actual);
    }

    /**
     * Parse camel case order types.
     *
     * @return void
     */
    public function testFromStringCamelCaseLowerCaseFirst(): void
    {
        $input = 'preAuth';
        $expected = 'PreAuth';
        $actual = OrderTypes::fromString($input)->value;
        $this->assertEquals($expected, $actual);
    }

    /**
     * Parse lowercase snake case order types.
     *
     * @return void
     */
    public function testFromStringSnakeCaseAllLowerCase(): void
    {
        $input = 'pre_auth';
        $expected = 'PreAuth';
        $actual = OrderTypes::fromString($input)->value;
        $this->assertEquals($expected, $actual);
    }

    /**
     * Parse the purchase order type.
     *
     * @return void
     */
    public function testFromStringOneWordCaseFirst(): void
    {
        $input = 'Purchase';
        $expected = 'Purchase';
        $actual = OrderTypes::fromString($input)->value;
        $this->assertEquals($expected, $actual);
    }
}
