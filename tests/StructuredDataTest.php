<?php

namespace tests;

use OpenPaymentSolutions\TranzWarePaymentGateway\Data\HTTPInfo;
use OpenPaymentSolutions\TranzWarePaymentGateway\Data\OrderData;
use OpenPaymentSolutions\TranzWarePaymentGateway\Data\OrderStatusData;
use OpenPaymentSolutions\TranzWarePaymentGateway\Data\OrderCallbackData;
use OpenPaymentSolutions\TranzWarePaymentGateway\Handlers\OrderCallbackHandler;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\HTTPClientResult;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\OrderRequestResult;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\OrderStatusRequestResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class StructuredDataTest extends TestCase
{
    /**
     * Preserve extra XML fields without duplicating parsed fields.
     *
     * @return void
     */
    public function testCreationDataRetainsUnknownFieldsAtTheirOriginalPaths(): void
    {
        $body = '<TKKPG version="1"><RootExtra>root</RootExtra><Response><Status>00</Status>'
            . '<Trace>response trace</Trace><Order><OrderID>00123</OrderID><SessionID>session</SessionID>'
            . '<URL>https://gateway.example/pay</URL><Trace>order trace</Trace>'
            . '<Extra code="x">before<Child>nested</Child>after</Extra>'
            . '<Tag>first</Tag><Tag>second</Tag><Empty/><Cdata><![CDATA[A&B]]></Cdata>'
            . '</Order></Response></TKKPG>';
        $data = (new OrderRequestResult(new HTTPClientResult($body, ['http_code' => 200])))->getData();
        $this->assertInstanceOf(OrderData::class, $data);
        $this->assertSame('00123', $data->orderId);
        $this->assertSame('session', $data->sessionId);
        $this->assertSame('https://gateway.example/pay', $data->url);
        $this->assertSame('https://gateway.example/pay?ORDERID=00123&SESSIONID=session', $data->paymentUrl);
        $this->assertSame([
            '@attributes' => ['version' => '1'],
            'RootExtra' => 'root',
            'Response' => [
                'Trace' => 'response trace',
                'Order' => [
                    'Trace' => 'order trace',
                    'Extra' => ['@attributes' => ['code' => 'x'], 'Child' => 'nested', '#text' => 'beforeafter'],
                    'Tag' => ['first', 'second'],
                    'Empty' => '',
                    'Cdata' => 'A&B',
                ],
            ],
        ], $data->__unparsed);
    }

    /**
     * Keep status details in their existing accessor and preserve additional gateway values.
     *
     * @return void
     */
    public function testStatusDataRetainsUnknownFieldsAndKnownFieldAttributes(): void
    {
        $body = '<TKKPG><Response><Status source="gateway">00</Status><Details>checked</Details>'
            . '<Trace>response trace</Trace><Order><OrderID>123</OrderID><OrderStatus>DECLINED</OrderStatus>'
            . '<AuthCode>0000</AuthCode></Order></Response></TKKPG>';
        $result = new OrderStatusRequestResult(new HTTPClientResult($body, ['http_code' => 200]));
        $data = $result->getData();
        $this->assertInstanceOf(OrderStatusData::class, $data);
        $this->assertSame('123', $data->orderId);
        $this->assertSame('DECLINED', $data->orderStatus);
        $this->assertSame('checked', $result->getDetails());
        $this->assertFalse($result->success());
        $this->assertSame(['Response' => [
            'Status' => ['@attributes' => ['source' => 'gateway']],
            'Trace' => 'response trace',
            'Order' => ['AuthCode' => '0000'],
        ]], $data->__unparsed);
    }

    /**
     * Preserve callback extras for raw XML and base64 messages.
     *
     * @param bool $encoded
     *
     * @see    self::encodings()
     * @return void
     */
    #[DataProvider('encodings')]
    public function testCallbackDataRetainsNamespacedAndRepeatedExtras(bool $encoded): void
    {
        $xml = '<Order xmlns:bank="urn:bank" bank:version="1"><TranDateTime>today</TranDateTime>'
            . '<OrderID>123</OrderID><PurchaseAmount>100</PurchaseAmount><Currency>840</Currency>'
            . '<OrderStatus>APPROVED</OrderStatus><Note>one</Note><Note>two</Note>'
            . '<bank:OrderID>bank identifier</bank:OrderID><Currency xmlns="urn:extra">other</Currency>'
            . '<__unparsed>gateway value</__unparsed></Order>';
        $message = $encoded ? base64_encode($xml) : $xml;
        $previous = $_REQUEST;
        try {
            $_REQUEST = ['xmlmsg' => $message];
            $data = (new OrderCallbackHandler())->handle();
        } finally {
            $_REQUEST = $previous;
        }
        $this->assertInstanceOf(OrderCallbackData::class, $data);
        $this->assertSame('123', $data->orderId);
        $this->assertSame('100', $data->amount);
        $this->assertSame('840', $data->currency);
        $this->assertSame($message, $data->xmlMessage);
        $this->assertSame([
            '@attributes' => ['bank:version' => '1'],
            'Note' => ['one', 'two'],
            'bank:OrderID' => 'bank identifier',
            '{urn:extra}Currency' => 'other',
            '__unparsed' => 'gateway value',
        ], $data->__unparsed);
        $json = json_decode(json_encode($data, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($data->__unparsed, $json['__unparsed']);
    }

    /**
     * Provide raw XML and base64 callback encodings.
     *
     * @return array
     */
    public static function encodings(): array
    {
        return [[false], [true]];
    }

    /**
     * Preserve unknown fields even when the gateway rejects order creation.
     *
     * @return void
     */
    public function testRejectedOrderStillReturnsStructuredExtraData(): void
    {
        $body = '<TKKPG><Response><Status>30</Status><Details>Rejected</Details></Response></TKKPG>';
        $result = new OrderRequestResult(new HTTPClientResult($body, ['http_code' => 400]));
        $this->assertFalse($result->success());
        $this->assertNull($result->getData()->paymentUrl);
        $this->assertSame(['Response' => ['Details' => 'Rejected']], $result->getData()->__unparsed);
    }

    /**
     * Normalize known HTTP fields while retaining the remaining transport information.
     *
     * @return void
     */
    public function testHttpInfoHasTypedFieldsAndRetainsAdditionalValues(): void
    {
        $info = new HTTPInfo([
            'http_code' => 200,
            'url' => 'https://gateway.example/api',
            'content_type' => 'application/xml',
            'total_time' => 0.125,
            'ssl_verify_result' => 0,
            'custom' => ['nested' => true],
        ]);
        $result = new HTTPClientResult('response', $info);
        $this->assertSame($info, $result->getInfo());
        $this->assertSame(200, $info->httpCode);
        $this->assertSame('https://gateway.example/api', $info->url);
        $this->assertSame('application/xml', $info->contentType);
        $this->assertSame(0.125, $info->totalTime);
        $this->assertSame(['ssl_verify_result' => 0, 'custom' => ['nested' => true]], $info->__unparsed);
    }
}
