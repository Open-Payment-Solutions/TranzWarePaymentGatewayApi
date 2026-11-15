<?php

namespace tests;

use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\HTTPClientResult;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\OrderRequestResult;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\OrderStatusRequestResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use OpenPaymentSolutions\TranzWarePaymentGateway\Data\OrderData;
use OpenPaymentSolutions\TranzWarePaymentGateway\Data\OrderStatusData;

final class RequestResultsTest extends TestCase
{
    /**
     * Treat empty responses as unsuccessful results.
     *
     * @param class-string $class
     * @param string|false $body
     *
     * @see    self::emptyResults()
     * @return void
     */
    #[DataProvider('emptyResults')]
    public function testEmptyResponsesAreUnsuccessful(string $class, string|false $body): void
    {
        $result = new $class(new HTTPClientResult($body, []));
        $this->assertFalse($result->success());
        $this->assertNull($result->getStatus());
        $this->assertInstanceOf(
            $class === OrderRequestResult::class ? OrderData::class : OrderStatusData::class,
            $result->getData()
        );
        $this->assertNull($result->getData()->orderId);
        $this->assertSame([], $result->getData()->__unparsed);
        $this->assertSame(0, $result->getHttpStatus());
        $this->assertSame($body, $result->getResponseBody());
    }

    /**
     * Provide empty and failed transport responses for both result classes.
     *
     * @return array
     */
    public static function emptyResults(): array
    {
        return [[OrderRequestResult::class, ''], [OrderRequestResult::class, false],
            [OrderStatusRequestResult::class, ''], [OrderStatusRequestResult::class, false]];
    }

    /**
     * Reject malformed responses without PHP warnings.
     *
     * @param class-string $class
     * @param string $body
     *
     * @see    self::invalidResults()
     * @return void
     */
    #[DataProvider('invalidResults')]
    public function testMalformedResponsesThrowWithoutPhpWarnings(string $class, string $body): void
    {
        $this->expectException(\UnexpectedValueException::class);
        new $class(new HTTPClientResult($body, ['http_code' => 502]));
    }

    /**
     * Provide malformed gateway responses for both result classes.
     *
     * @return array
     */
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

    /**
     * Preserve the gateway status and raw response for rejected orders.
     *
     * @return void
     */
    public function testRejectedOrderExposesGatewayStatusAndRawResponse(): void
    {
        $body = '<TKKPG><Response><Status>30</Status></Response></TKKPG>';
        $result = new OrderRequestResult(new HTTPClientResult($body, ['http_code' => 400]));
        $this->assertFalse($result->success());
        $this->assertSame('30', $result->getStatus());
        $this->assertInstanceOf(OrderData::class, $result->getData());
        $this->assertNull($result->getData()->paymentUrl);
        $this->assertSame([], $result->getData()->__unparsed);
        $this->assertSame(400, $result->getHttpStatus());
        $this->assertSame($body, $result->getResponseBody());
    }

    /**
     * Reject successful creation responses with missing payment identifiers.
     *
     * @return void
     */
    public function testSuccessfulOrderWithMissingIdentifiersThrows(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        new OrderRequestResult(new HTTPClientResult(
            '<TKKPG><Response><Status>00</Status><Order/></Response></TKKPG>',
            []
        ));
    }

    /**
     * Expose order identifiers, status, and gateway details.
     *
     * @param string $status
     * @param bool $approved
     *
     * @see    self::statuses()
     * @return void
     */
    #[DataProvider('statuses')]
    public function testStatusResultsExposeOrderIdAndDetails(string $status, bool $approved): void
    {
        $body = '<TKKPG><Response><Status>00</Status><Details>Test details</Details><Order>'
            . '<OrderID>123</OrderID><OrderStatus>' . $status . '</OrderStatus></Order></Response></TKKPG>';
        $result = new OrderStatusRequestResult(new HTTPClientResult($body, ['http_code' => 200]));
        $this->assertInstanceOf(OrderStatusData::class, $result->getData());
        $this->assertSame('123', $result->getData()->orderId);
        $this->assertSame($status, $result->getData()->orderStatus);
        $this->assertSame([], $result->getData()->__unparsed);
        $this->assertSame('Test details', $result->getDetails());
        $this->assertSame($approved, $result->success());
        $this->assertSame($body, $result->getResponseBody());
        $this->assertSame(200, $result->getHttpStatus());
    }

    /**
     * Provide order outcomes and their expected approval flags.
     *
     * @return array
     */
    public static function statuses(): array
    {
        return [['APPROVED', true], ['DECLINED', false], ['CANCELED', false], ['CREATED', false]];
    }

    /**
     * Report missing orders through gateway details or diagnostics.
     *
     * @param string $details
     * @param string $message
     *
     * @see    self::gatewayErrors()
     * @return void
     */
    #[DataProvider('gatewayErrors')]
    public function testMissingOrderThrowsGatewayDetailsOrDiagnostic(string $details, string $message): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($message);
        new OrderStatusRequestResult(new HTTPClientResult(
            '<TKKPG><Response><Status>30</Status>' . $details . '</Response></TKKPG>',
            []
        ));
    }

    /**
     * Provide gateway error details and fallback diagnostics.
     *
     * @return array
     */
    public static function gatewayErrors(): array
    {
        return [['<Details>Order not found</Details>', 'Order not found'], ['', 'Unhandled situation.']];
    }

    /**
     * Parse empty details and CDATA status values as strings.
     *
     * @return void
     */
    public function testEmptyDetailsAndCdataAreParsedAsStrings(): void
    {
        $body = '<TKKPG><Response><Status>00</Status><Details/><Order><OrderID>123</OrderID>'
            . '<OrderStatus><![CDATA[APPROVED]]></OrderStatus></Order></Response></TKKPG>';
        $result = new OrderStatusRequestResult(new HTTPClientResult($body, []));
        $this->assertSame('', $result->getDetails());
        $this->assertTrue($result->success());
        $this->assertSame('123', $result->getData()->orderId);
    }

    /**
     * Keep an order identifier without a status unapproved.
     *
     * @return void
     */
    public function testOrderIdAloneDoesNotMeanApproved(): void
    {
        $result = new OrderStatusRequestResult(new HTTPClientResult(
            '<TKKPG><Response><Status>00</Status><Order><OrderID>123</OrderID></Order></Response></TKKPG>',
            []
        ));
        $this->assertSame('123', $result->getData()->orderId);
        $this->assertNull($result->getData()->orderStatus);
        $this->assertFalse($result->success());
        $this->assertSame('', $result->getDetails());
    }
}
