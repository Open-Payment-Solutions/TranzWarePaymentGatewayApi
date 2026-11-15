<?php

namespace tests;

use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidOrderId;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidSessionId;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\OrderStatusRetrieveException;
use OpenPaymentSolutions\TranzWarePaymentGateway\RequestFactory;
use OpenPaymentSolutions\TranzWarePaymentGateway\Requests\OrderStatusRequest;
use PHPUnit\Framework\TestCase;
use tests\fixtures\Transport;

final class OrderStatusRequestTest extends TestCase
{
    protected function setUp(): void
    {
        Transport::$enabled = true;
        Transport::$options = [];
        Transport::$info = ['http_code' => 200];
        Transport::$output = self::response('00', 'APPROVED');
    }

    protected function tearDown(): void
    {
        Transport::$enabled = false;
        Transport::$options = [];
    }

    private static function response(string $status, string $orderStatus): string
    {
        return '<TKKPG><Response><Status>' . $status . '</Status><Order><OrderID>123</OrderID>'
            . '<OrderStatus>' . $orderStatus . '</OrderStatus></Order></Response></TKKPG>';
    }

    /** @dataProvider invalidIdentifiers */
    public function testMissingIdentifiersAreRejectedBeforeTransport(bool $factory, ?string $orderId, ?string $sessionId, string $exception): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($exception === InvalidOrderId::class ? 'Invalid order ID' : 'Invalid session ID');
        try {
            if ($factory) {
                (new RequestFactory('https://gateway.example', 'merchant', 'a', 'd', 'c'))
                    ->createOrderStatusRequest($orderId, $sessionId);
            } else {
                new OrderStatusRequest('https://gateway.example', 'merchant', $orderId, $sessionId);
            }
        } finally {
            $this->assertSame([], Transport::$options);
        }
    }

    public static function invalidIdentifiers(): array
    {
        $rows = [];
        foreach ([true, false] as $factory) {
            foreach ([null, '', " \t\n"] as $identifier) {
                $rows[] = [$factory, $identifier, 'session', InvalidOrderId::class];
                $rows[] = [$factory, '123', $identifier, InvalidSessionId::class];
            }
        }
        return $rows;
    }

    /** @dataProvider failedResponses */
    public function testRetrievalFailuresUseSpecificExceptionAndPreserveCause(string|false $body, int $httpStatus, string $cause): void
    {
        Transport::$output = $body;
        Transport::$info = ['http_code' => $httpStatus];
        try {
            (new OrderStatusRequest('https://gateway.example', 'merchant', '123', 'session'))->execute();
            $this->fail('Expected a status retrieval exception');
        } catch (OrderStatusRetrieveException $e) {
            $this->assertSame('Unable to retrieve order status.', $e->getMessage());
            $this->assertInstanceOf($cause, $e->getPrevious());
        }
    }

    public static function failedResponses(): array
    {
        return [
            [false, 0, \RuntimeException::class],
            ['', 200, \RuntimeException::class],
            ['invalid XML', 200, \UnexpectedValueException::class],
            [self::response('00', 'APPROVED'), 500, \RuntimeException::class],
            [self::response('30', 'APPROVED'), 200, \UnexpectedValueException::class],
            [self::response('00', ''), 200, \UnexpectedValueException::class],
            ['<TKKPG><Response><Status>30</Status><Details>Unknown order</Details></Response></TKKPG>', 200, \Exception::class],
            ['<TKKPG><Response><Status>00</Status><Order><OrderID>123</OrderID></Order></Response></TKKPG>', 200, \UnexpectedValueException::class],
        ];
    }

    public function testTransportSetupFailureIsWrapped(): void
    {
        $request = new OrderStatusRequest('https://gateway.example', 'merchant', '123', 'session', 'EN', '/nonexistent-twpg-directory/debug.log');
        $this->expectException(OrderStatusRetrieveException::class);
        $request->execute();
    }

    /** @dataProvider retrievedStatuses */
    public function testOrderOutcomeDoesNotCauseRetrievalException(string $status, bool $approved): void
    {
        Transport::$output = self::response('00', $status);
        $result = (new OrderStatusRequest('https://gateway.example', 'merchant', '123', 'session'))->execute();
        $this->assertSame($approved, $result->success());
        $this->assertSame($status, $result->getData()['OrderStatus']);
    }

    public static function retrievedStatuses(): array
    {
        return [['APPROVED', true], ['DECLINED', false], ['CANCELED', false], ['CREATED', false]];
    }

    public function testZeroIsNotAnEmptyIdentifier(): void
    {
        (new OrderStatusRequest('https://gateway.example', 'merchant', '0', '0'))->execute();
        $xml = simplexml_load_string(Transport::$options[CURLOPT_POSTFIELDS]);
        $this->assertSame('0', (string)$xml->Request->Order->OrderID);
        $this->assertSame('0', (string)$xml->Request->SessionID);
    }
}
