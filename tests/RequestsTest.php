<?php

namespace tests;

use OpenPaymentSolutions\TranzWarePaymentGateway\OrderTypes;
use OpenPaymentSolutions\TranzWarePaymentGateway\CurrencyCodes;
use OpenPaymentSolutions\TranzWarePaymentGateway\RequestFactory;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\OrderRequest;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\OrderStatusRequest;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\OrderRequestResult;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\OrderStatusRequestResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use tests\fixtures\Transport;

final class RequestsTest extends TestCase
{
    /**
     * Prepare the simulated HTTP transport.
     *
     * @return void
     */
    protected function setUp(): void
    {
        Transport::$enabled = true;
        Transport::$options = [];
        Transport::$output = '<TKKPG><Response><Status>00</Status><Order><URL>https://pay.example</URL>'
            . '<OrderID>123</OrderID><SessionID>session</SessionID>'
            . '<OrderStatus>APPROVED</OrderStatus></Order></Response></TKKPG>';
        Transport::$info = ['http_code' => 200];
    }

    /**
     * Reset the simulated HTTP transport.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Transport::$enabled = false;
        Transport::$options = [];
    }

    /**
     * Create a request factory for testing.
     *
     * @return RequestFactory
     */
    private function factory(): RequestFactory
    {
        return new RequestFactory(
            'https://gateway.example',
            'merchant&123',
            'https://shop.example/approved?a=1&b=2',
            'https://shop.example/declined',
            'https://shop.example/canceled',
            'AZ'
        );
    }

    /**
     * Include configured values in order XML.
     *
     * @param OrderTypes|string $type
     * @param string $expected
     *
     * @see    self::orderTypes()
     * @return void
     */
    #[DataProvider('orderTypes')]
    public function testOrderXmlContainsConfiguredValues(OrderTypes|string $type, string $expected): void
    {
        $factory = $this->factory();
        $this->assertSame($factory, $factory->setCertificate('/client.crt', '/client.key', 'pass'));
        $result = $factory->createOrderRequest(12.5, '944', 'A&B <test> "quoted"', $type)->execute();
        $this->assertInstanceOf(OrderRequestResult::class, $result);
        $xml = simplexml_load_string(Transport::$options[CURLOPT_POSTFIELDS]);
        $this->assertSame('CreateOrder', (string)$xml->Request->Operation);
        $this->assertSame('AZ', (string)$xml->Request->Language);
        $order = $xml->Request->Order;
        $this->assertSame($expected, (string)$order->OrderType);
        $this->assertSame('merchant&123', (string)$order->Merchant);
        $this->assertSame('12.5', (string)$order->Amount);
        $this->assertSame('944', (string)$order->Currency);
        $this->assertSame('A&B <test> "quoted"', (string)$order->Description);
        $this->assertSame('https://shop.example/approved?a=1&b=2', (string)$order->ApproveURL);
        $this->assertSame('https://shop.example/declined', (string)$order->DeclineURL);
        $this->assertSame('https://shop.example/canceled', (string)$order->CancelURL);
        $this->assertSame('/client.crt', Transport::$options[CURLOPT_SSLCERT]);
        $this->assertSame('/client.key', Transport::$options[CURLOPT_SSLKEY]);
        $this->assertSame('pass', Transport::$options[CURLOPT_SSLCERTPASSWD]);
        $this->assertSame(2, Transport::$options[CURLOPT_SSL_VERIFYHOST]);
    }

    /**
     * Provide supported order types and their normalized values.
     *
     * @return array
     */
    public static function orderTypes(): array
    {
        return [[OrderTypes::PURCHASE, 'Purchase'], [OrderTypes::PRE_AUTH, 'PreAuth'],
            ['pre_auth', 'PreAuth'], ['purchase', 'Purchase']];
    }

    /**
     * Serialize currency enum cases as numeric gateway codes.
     *
     * @return void
     */
    public function testCurrencyEnumIsSerializedAsNumericCode(): void
    {
        $this->factory()->createOrderPurchaseRequest(1, CurrencyCodes::EUR)->execute();
        $xml = simplexml_load_string(Transport::$options[CURLOPT_POSTFIELDS]);
        $this->assertSame('978', (string)$xml->Request->Order->Currency);
    }

    /**
     * Accept numeric currency strings outside the currency enum.
     *
     * @return void
     */
    public function testUnsupportedNumericCurrencyCanStillBeSentAsString(): void
    {
        $this->factory()->createOrderPurchaseRequest(1, '826')->execute();
        $xml = simplexml_load_string(Transport::$options[CURLOPT_POSTFIELDS]);
        $this->assertSame('826', (string)$xml->Request->Order->Currency);
    }

    /**
     * Reject invalid order types before sending a request.
     *
     * @return void
     */
    public function testInvalidOrderTypeIsRejectedBeforeTransport(): void
    {
        $this->expectException(\OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidOrderType::class);
        $this->factory()->createOrderRequest(1, '840', '', 'unknown');
    }

    /**
     * Escape order and session identifiers in status request XML.
     *
     * @return void
     */
    public function testStatusXmlAndIdentifiersAreEscaped(): void
    {
        $result = $this->factory()->createOrderStatusRequest('123<&', 'session&<>')->execute();
        $this->assertInstanceOf(OrderStatusRequestResult::class, $result);
        $xml = simplexml_load_string(Transport::$options[CURLOPT_POSTFIELDS]);
        $this->assertSame('GetOrderStatus', (string)$xml->Request->Operation);
        $this->assertSame('AZ', (string)$xml->Request->Language);
        $this->assertSame('merchant&123', (string)$xml->Request->Order->Merchant);
        $this->assertSame('123<&', (string)$xml->Request->Order->OrderID);
        $this->assertSame('session&<>', (string)$xml->Request->SessionID);
    }

    /**
     * Preserve or override the configured SSL verification mode.
     *
     * @param string $mode
     * @param bool|null $certificateMode
     * @param bool $expected
     *
     * @see    self::certificateModes()
     * @return void
     */
    #[DataProvider('certificateModes')]
    public function testFactoryPreservesAndOverridesSslMode(string $mode, ?bool $certificateMode, bool $expected): void
    {
        $factory = $this->factory();
        if ($mode !== '') {
            $this->assertSame($factory, $factory->$mode());
        }
        $factory->setCertificate('/cert', '/key', '', $certificateMode);
        foreach ([$factory->createOrderRequest(1, '840'), $factory->createOrderStatusRequest('1', 's')] as $request) {
            $request->execute();
            $this->assertSame($expected, Transport::$options[CURLOPT_SSL_VERIFYPEER]);
        }
    }

    /**
     * Provide SSL verification defaults and explicit overrides.
     *
     * @return array
     */
    public static function certificateModes(): array
    {
        return [['', null, true], ['disableSSLVerification', null, false],
            ['enableSSLVerification', null, true], ['disableSSLVerification', true, true],
            ['enableSSLVerification', false, false]];
    }

    /**
     * Select the expected order type through each convenience method.
     *
     * @return void
     */
    public function testConvenienceMethodsSelectCorrectOrderTypes(): void
    {
        $factory = $this->factory();
        foreach (
            [
            'createOrderPurchaseRequest' => 'Purchase',
            'createOrderPreAuthRequest' => 'PreAuth',
            ] as $method => $expected
        ) {
            $factory->$method(1, '840')->execute();
            $xml = simplexml_load_string(Transport::$options[CURLOPT_POSTFIELDS]);
            $this->assertSame($expected, (string)$xml->Request->Order->OrderType);
        }
    }

    /**
     * Pass debug configuration to order and status requests.
     *
     * @return void
     */
    public function testDebugConfigurationReachesBothRequestTypes(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'twpg-test-');
        try {
            $factory = $this->factory();
            $this->assertSame($factory, $factory->setDebugFile($file));
            foreach (
                [
                $factory->createOrderRequest(1, '840'),
                $factory->createOrderStatusRequest('1', 's'),
                ] as $request
            ) {
                $request->execute();
                $this->assertTrue(Transport::$options[CURLOPT_VERBOSE]);
                $this->assertStringContainsString('<TKKPG>', file_get_contents($file));
            }
        } finally {
            unlink($file);
        }
    }

    /**
     * Apply changes to request SSL settings after construction.
     *
     * @return void
     */
    public function testRequestSettingsCanBeChangedAfterConstruction(): void
    {
        $requests = [new OrderRequest('https://gateway.example', 'a', 'd', 'c', OrderTypes::PURCHASE, 'm', 1, '840'),
            new OrderStatusRequest('https://gateway.example', 'm', '1', 's')];
        foreach ($requests as $request) {
            $request->setSslCertificate('/cert', '/key', 'pass');
            $request->disableSSLVerification();
            $request->execute();
            $this->assertFalse(Transport::$options[CURLOPT_SSL_VERIFYPEER]);
            $request->enableSSLVerification();
            $request->execute();
            $this->assertTrue(Transport::$options[CURLOPT_SSL_VERIFYPEER]);
            $request->setStrictSSL(false);
            $request->execute();
            $this->assertFalse(Transport::$options[CURLOPT_SSL_VERIFYPEER]);
        }
    }
}
