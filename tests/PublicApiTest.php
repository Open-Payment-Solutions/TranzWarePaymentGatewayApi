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

final class PublicApiTest extends TestCase
{
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

    public function testFactoryCreatesOrdersAndStatusRequestsUsingShortNames()
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

    /** @dataProvider emptyIdentifiers */
    public function testStatusRequestStillRejectsEmptyIdentifiers($orderId, $sessionId, $exception)
    {
        $this->expectException($exception);
        $this->factory()->createOrderStatusRequest($orderId, $sessionId);
    }

    public static function emptyIdentifiers(): array
    {
        return [
            ['123', '', \OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidSessionId::class],
            ['', 'session-test', \OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidOrderId::class],
        ];
    }

    /** @dataProvider callbackMessages */
    public function testCallbackFactoryParsesXmlAndBase64($message)
    {
        $previous = $_REQUEST;
        try {
            $_REQUEST = ['xmlmsg' => $message];
            $factory = new HandlerFactory();
            $this->assertInstanceOf(HandlerFactoryInterface::class, $factory);
            $handler = $factory->createOrderCallbackHandler();
            $this->assertInstanceOf(HandlerInterface::class, $handler);
            $this->assertSame([
                'DateTime' => '2026-11-13 12:00:00',
                'OrderId' => '123',
                'Amount' => '100',
                'Currency' => '840',
                'OrderStatus' => 'APPROVED',
                'xmlmsg' => $message,
            ], $handler->handle());
        } finally {
            $_REQUEST = $previous;
        }
    }

    public static function callbackMessages(): array
    {
        $xml = '<Order><TranDateTime>2026-11-13 12:00:00</TranDateTime>'
            . '<OrderID>123</OrderID><PurchaseAmount>100</PurchaseAmount>'
            . '<Currency>840</Currency><OrderStatus>APPROVED</OrderStatus></Order>';
        return [[$xml], [base64_encode($xml)]];
    }

    public function testCreationResultExposesPaymentIdentifiersThroughRenamedInterfaces()
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
        $this->assertSame([
            'URL' => 'https://gateway.example/pay',
            'OrderId' => '123',
            'SessionId' => 'session-test',
            'PaymentUrl' => 'https://gateway.example/pay?ORDERID=123&SESSIONID=session-test',
        ], $result->getData());
    }

    /** @dataProvider orderStatuses */
    public function testStatusResultDistinguishesApprovalFromDecline($status, $approved)
    {
        $result = new OrderStatusRequestResult(new HTTPClientResult(
            '<TKKPG><Response><Status>00</Status><Order><OrderID>123</OrderID>'
            . '<OrderStatus>' . $status . '</OrderStatus></Order></Response></TKKPG>',
            ['http_code' => 200]
        ));

        $this->assertInstanceOf(RequestResultInterface::class, $result);
        $this->assertSame('00', $result->getStatus());
        $this->assertSame($status, $result->getData()['OrderStatus']);
        $this->assertSame($approved, $result->success());
    }

    public static function orderStatuses(): array
    {
        return [['APPROVED', true], ['DECLINED', false]];
    }
}
