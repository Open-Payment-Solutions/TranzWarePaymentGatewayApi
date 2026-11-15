<?php

namespace tests;

use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\HTTPClientResult;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\OrderRequestResult;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\OrderStatusRequestResult;
use PHPUnit\Framework\TestCase;

final class RequestResultsTest extends TestCase
{
    /** @dataProvider emptyResults */
    public function testEmptyResponsesAreUnsuccessful(string $class, string|false $body): void
    {
        $result = new $class(new HTTPClientResult($body, []));
        $this->assertFalse($result->success());
        $this->assertNull($result->getStatus());
        $this->assertSame([], $result->getData());
        $this->assertSame(0, $result->getHttpStatus());
        $this->assertSame($body, $result->getResponseBody());
    }

    public static function emptyResults(): array
    {
        return [[OrderRequestResult::class, ''], [OrderRequestResult::class, false],
            [OrderStatusRequestResult::class, ''], [OrderStatusRequestResult::class, false]];
    }

    /** @dataProvider invalidResults */
    public function testMalformedResponsesThrowWithoutPhpWarnings(string $class, string $body): void
    {
        $this->expectException(\UnexpectedValueException::class);
        new $class(new HTTPClientResult($body, ['http_code' => 502]));
    }

    public static function invalidResults(): array
    {
        $rows = [];
        foreach ([OrderRequestResult::class, OrderStatusRequestResult::class] as $class) {
            foreach (['garbage', '<TKKPG>', '<html>Unavailable</html>', '<TKKPG><Response/></TKKPG>'] as $body) {
                $rows[] = [$class, $body];
            }
        }
        return $rows;
    }

    public function testRejectedOrderExposesGatewayStatusAndRawResponse(): void
    {
        $body = '<TKKPG><Response><Status>30</Status></Response></TKKPG>';
        $result = new OrderRequestResult(new HTTPClientResult($body, ['http_code' => 400]));
        $this->assertFalse($result->success());
        $this->assertSame('30', $result->getStatus());
        $this->assertNull($result->getData());
        $this->assertSame(400, $result->getHttpStatus());
        $this->assertSame($body, $result->getResponseBody());
    }

    public function testSuccessfulOrderWithMissingIdentifiersThrows(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        new OrderRequestResult(new HTTPClientResult(
            '<TKKPG><Response><Status>00</Status><Order/></Response></TKKPG>',
            []
        ));
    }

    /** @dataProvider statuses */
    public function testStatusResultsExposeOrderIdAndDetails(string $status, bool $approved): void
    {
        $body = '<TKKPG><Response><Status>00</Status><Details>Test details</Details><Order>'
            . '<OrderID>123</OrderID><OrderStatus>' . $status . '</OrderStatus></Order></Response></TKKPG>';
        $result = new OrderStatusRequestResult(new HTTPClientResult($body, ['http_code' => 200]));
        $this->assertSame(['OrderId' => '123', 'OrderStatus' => $status], $result->getData());
        $this->assertSame('Test details', $result->getDetails());
        $this->assertSame($approved, $result->success());
        $this->assertSame($body, $result->getResponseBody());
        $this->assertSame(200, $result->getHttpStatus());
    }

    public static function statuses(): array
    {
        return [['APPROVED', true], ['DECLINED', false], ['CANCELED', false], ['CREATED', false]];
    }

    /** @dataProvider gatewayErrors */
    public function testMissingOrderThrowsGatewayDetailsOrDiagnostic(string $details, string $message): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($message);
        new OrderStatusRequestResult(new HTTPClientResult(
            '<TKKPG><Response><Status>30</Status>' . $details . '</Response></TKKPG>',
            []
        ));
    }

    public static function gatewayErrors(): array
    {
        return [['<Details>Order not found</Details>', 'Order not found'], ['', 'Unhandled situation.']];
    }

    public function testEmptyDetailsAndCdataAreParsedAsStrings(): void
    {
        $body = '<TKKPG><Response><Status>00</Status><Details/><Order><OrderID>123</OrderID>'
            . '<OrderStatus><![CDATA[APPROVED]]></OrderStatus></Order></Response></TKKPG>';
        $result = new OrderStatusRequestResult(new HTTPClientResult($body, []));
        $this->assertSame('', $result->getDetails());
        $this->assertTrue($result->success());
        $this->assertSame('123', $result->getData()['OrderId']);
    }

    public function testOrderIdAloneDoesNotMeanApproved(): void
    {
        $result = new OrderStatusRequestResult(new HTTPClientResult(
            '<TKKPG><Response><Status>00</Status><Order><OrderID>123</OrderID></Order></Response></TKKPG>',
            []
        ));
        $this->assertSame(['OrderId' => '123', 'OrderStatus' => null], $result->getData());
        $this->assertFalse($result->success());
        $this->assertSame('', $result->getDetails());
    }
}
