<?php

namespace tests;

use OpenPaymentSolutions\TranzWarePaymentGateway\Handlers\OrderCallbackHandler;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\EmptyXMLMessage;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidXMLMessage;
use PHPUnit\Framework\TestCase;

final class OrderCallbackHandlerTest extends TestCase
{
    /** @dataProvider invalidMessages */
    public function testInvalidCallbacksThrowSpecificExceptions(array $request, string $exception, string $message): void
    {
        $previous = $_REQUEST;
        try {
            $_REQUEST = $request;
            $this->expectException($exception);
            $this->expectExceptionMessage($message);
            (new OrderCallbackHandler())->handle();
        } finally {
            $_REQUEST = $previous;
        }
    }

    public static function invalidMessages(): array
    {
        return [
            [[], EmptyXMLMessage::class, 'Empty XML message'],
            [['xmlmsg' => null], EmptyXMLMessage::class, 'Empty XML message'],
            [['xmlmsg' => ''], EmptyXMLMessage::class, 'Empty XML message'],
            [['xmlmsg' => []], InvalidXMLMessage::class, 'Invalid XML message'],
            [['xmlmsg' => 123], InvalidXMLMessage::class, 'Invalid XML message'],
            [['xmlmsg' => false], InvalidXMLMessage::class, 'Invalid XML message'],
            [['xmlmsg' => ' '], InvalidXMLMessage::class, 'Invalid XML message'],
            [['xmlmsg' => '!!!'], InvalidXMLMessage::class, 'Invalid XML message'],
            [['xmlmsg' => '<Order>'], InvalidXMLMessage::class, 'Invalid XML message'],
            [['xmlmsg' => base64_encode('invalid XML')], InvalidXMLMessage::class, 'Invalid XML message'],
            [['xmlmsg' => '<Order><OrderID>1</OrderID></Order>'], InvalidXMLMessage::class, 'Invalid XML message'],
        ];
    }
}
