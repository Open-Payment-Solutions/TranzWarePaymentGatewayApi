<?php

namespace tests;

use OpenPaymentSolutions\TranzWarePaymentGateway\CurrencyCodes;
use OpenPaymentSolutions\TranzWarePaymentGateway\HandlerFactory;
use OpenPaymentSolutions\TranzWarePaymentGateway\HandlerFactoryInterface;
use OpenPaymentSolutions\TranzWarePaymentGateway\Handlers\HandlerInterface;
use OpenPaymentSolutions\TranzWarePaymentGateway\RequestFactory;
use OpenPaymentSolutions\TranzWarePaymentGateway\RequestFactoryInterface;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\HTTPClientResult;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\HTTPClientResultInterface;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\OrderRequest;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\OrderRequestResult;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\OrderStatusRequest;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\OrderStatusRequestResult;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\RequestInterface;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\RequestResultInterface;
use PHPUnit\Framework\TestCase;
use OpenPaymentSolutions\TranzWarePaymentGateway\Data\OrderCallbackData;
use OpenPaymentSolutions\TranzWarePaymentGateway\Data\OrderData;

final class PublicApiTest extends TestCase
{
    /**
     * Create a request factory for testing.
     *
     * @return RequestFactoryInterface
     */
    private function factory(): RequestFactoryInterface
    {
        return (new RequestFactory(
            'https://gateway.example/api',
            'merchant-test',
            'https://shop.example/approved',
            'https://shop.example/declined',
            'https://shop.example/canceled'
        ))->setCertificate('/test/client.crt', '/test/client.key', '');
    }

    /**
     * Create order and status requests through the public API.
     *
     * @return void
     */
    public function testFactoryCreatesOrdersAndStatusRequestsUsingShortNames(): void
    {
        $factory = $this->factory();
        $purchase = $factory->createOrderPurchaseRequest(100, CurrencyCodes::USD, 'Checkout 123');
        $preAuth = $factory->createOrderPreAuthRequest(100, CurrencyCodes::USD, 'Reservation 123');
        $default = $factory->createOrderRequest(100, CurrencyCodes::USD);
        $status = $factory->createOrderStatusRequest('123', 'session-test');

        foreach ([$purchase, $preAuth, $default] as $request) {
            $this->assertInstanceOf(OrderRequest::class, $request);
            $this->assertInstanceOf(RequestInterface::class, $request);
        }
        $this->assertInstanceOf(OrderStatusRequest::class, $status);
        $this->assertInstanceOf(RequestInterface::class, $status);
    }

    /**
     * Reject empty order and session identifiers.
     *
     * @param string $orderId
     * @param string $sessionId
     * @param class-string<\Throwable> $exception
     *
     * @dataProvider emptyIdentifiers
     * @return       void
     */
    public function testStatusRequestStillRejectsEmptyIdentifiers($orderId, $sessionId, $exception): void
    {
        $this->expectException($exception);
        $this->factory()->createOrderStatusRequest($orderId, $sessionId);
    }

    /**
     * Provide invalid order and session identifiers.
     *
     * @return array
     */
    public static function emptyIdentifiers(): array
    {
        return [
            ['123', '', \OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidSessionId::class],
            ['', 'session-test', \OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidOrderId::class],
        ];
    }

    /**
     * Parse raw XML and base64 callback messages.
     *
     * @param string $message
     *
     * @dataProvider callbackMessages
     * @return       void
     */
    public function testCallbackFactoryParsesXmlAndBase64($message): void
    {
        $previous = $_REQUEST;
        try {
            $_REQUEST = ['xmlmsg' => $message];
            $factory = new HandlerFactory();
            $this->assertInstanceOf(HandlerFactoryInterface::class, $factory);
            $handler = $factory->createOrderCallbackHandler();
            $this->assertInstanceOf(HandlerInterface::class, $handler);
            $data = $handler->handle();
            $this->assertInstanceOf(OrderCallbackData::class, $data);
            $this->assertSame([
                'dateTime' => '2026-11-13 12:00:00',
                'orderId' => '123',
                'amount' => '100',
                'currency' => '840',
                'orderStatus' => 'APPROVED',
                'xmlMessage' => $message,
                '__unparsed' => [],
            ], get_object_vars($data));
        } finally {
            $_REQUEST = $previous;
        }
    }

    /**
     * Provide raw XML and base64 callback messages.
     *
     * @return array
     */
    public static function callbackMessages(): array
    {
        $xml = '<Order><TranDateTime>2026-11-13 12:00:00</TranDateTime>'
            . '<OrderID>123</OrderID><PurchaseAmount>100</PurchaseAmount>'
            . '<Currency>840</Currency><OrderStatus>APPROVED</OrderStatus></Order>';
        return [[$xml], [base64_encode($xml)]];
    }

    /**
     * Expose payment identifiers through the public result interfaces.
     *
     * @return void
     */
    public function testCreationResultExposesPaymentIdentifiersThroughRenamedInterfaces(): void
    {
        $httpResult = new HTTPClientResult(
            '<TKKPG><Response><Status>00</Status><Order><OrderID>123</OrderID>'
            . '<SessionID>session-test</SessionID><URL>https://gateway.example/pay</URL>'
            . '</Order></Response></TKKPG>',
            ['http_code' => 200]
        );
        $this->assertInstanceOf(HTTPClientResultInterface::class, $httpResult);
        $result = new OrderRequestResult($httpResult);

        $this->assertInstanceOf(RequestResultInterface::class, $result);
        $this->assertSame(200, $result->getHttpStatus());
        $this->assertSame('00', $result->getStatus());
        $this->assertTrue($result->success());
        $this->assertInstanceOf(OrderData::class, $result->getData());
        $this->assertSame([
            'url' => 'https://gateway.example/pay',
            'orderId' => '123',
            'sessionId' => 'session-test',
            'paymentUrl' => 'https://gateway.example/pay?ORDERID=123&SESSIONID=session-test',
            '__unparsed' => [],
        ], get_object_vars($result->getData()));
    }

    /**
     * Distinguish approved and declined order statuses.
     *
     * @param string $status
     * @param bool $approved
     *
     * @dataProvider orderStatuses
     * @return       void
     */
    public function testStatusResultDistinguishesApprovalFromDecline($status, $approved): void
    {
        $result = new OrderStatusRequestResult(new HTTPClientResult(
            '<TKKPG><Response><Status>00</Status><Order><OrderID>123</OrderID>'
            . '<OrderStatus>' . $status . '</OrderStatus></Order></Response></TKKPG>',
            ['http_code' => 200]
        ));

        $this->assertInstanceOf(RequestResultInterface::class, $result);
        $this->assertSame('00', $result->getStatus());
        $this->assertSame($status, $result->getData()->orderStatus);
        $this->assertSame($approved, $result->success());
    }

    /**
     * Provide approved and declined order outcomes.
     *
     * @return array
     */
    public static function orderStatuses(): array
    {
        return [['APPROVED', true], ['DECLINED', false]];
    }
}
