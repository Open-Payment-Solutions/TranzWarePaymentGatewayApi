<?php

namespace tests;

use \OpenPaymentSolutions\TranzWarePaymentGateway\OrderTypes;

use \PHPUnit\Framework\TestCase;

final class OrderTypesTest extends TestCase
{
    public function testOrderTypeSanitizerReturnsEmptyString()
    {
        $input = '  _-,./?\!@#$%^&*()+= ';
        $actual = OrderTypes::sanitizeValue($input);
        $this->assertEmpty($actual);
    }

    public function testOrderTypeSanitizerSnakeCase()
    {
        $input = 'PRE_AUTH';
        $expected = 'PreAuth';
        $actual = OrderTypes::sanitizeValue($input);
        $this->assertEquals($expected, $actual);
    }

    public function testOrderTypeSanitizerCamelCaseLowerCaseFirst()
    {
        $input = 'preAuth';
        $expected = 'PreAuth';
        $actual = OrderTypes::sanitizeValue($input);
        $this->assertEquals($expected, $actual);
    }

    public function testOrderTypeSanitizerSnakeCaseAllLowerCase()
    {
        $input = 'pre_auth';
        $expected = 'PreAuth';
        $actual = OrderTypes::sanitizeValue($input);
        $this->assertEquals($expected, $actual);
    }

    public function testValidationInvalidType()
    {
        $input = 'FakeAuth';
        $actual = OrderTypes::isValid($input);
        $this->assertFalse($actual);
    }

    public function testValidationValidType()
    {
        $input = 'PreAuth';
        $actual = OrderTypes::isValid($input);
        $this->assertTrue($actual);
    }

    public function testFromStringSnakeCase()
    {
        $input = 'PRE_AUTH';
        $expected = 'PreAuth';
        $actual = OrderTypes::fromString($input);
        $this->assertEquals($expected, $actual);
    }

    public function testFromStringCamelCaseLowerCaseFirst()
    {
        $input = 'preAuth';
        $expected = 'PreAuth';
        $actual = OrderTypes::fromString($input);
        $this->assertEquals($expected, $actual);
    }

    public function testFromStringSnakeCaseAllLowerCase()
    {
        $input = 'pre_auth';
        $expected = 'PreAuth';
        $actual = OrderTypes::fromString($input);
        $this->assertEquals($expected, $actual);
    }

    public function testFromStringOneWordCaseFirst()
    {
        $input = 'Purchase';
        $expected = 'Purchase';
        $actual = OrderTypes::fromString($input);
        $this->assertEquals($expected, $actual);
    }
}
