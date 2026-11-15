<?php

/**
 * Create a purchase order and display its payment link.
 */

$autoloadFile = __DIR__ . '/vendor/autoload.php';
require_once is_file($autoloadFile) ? $autoloadFile : __DIR__ . '/../vendor/autoload.php';

use OpenPaymentSolutions\TranzWarePaymentGateway\RequestFactory;
use OpenPaymentSolutions\TranzWarePaymentGateway\CurrencyCodes;
use OpenPaymentSolutions\TranzWarePaymentGateway\OrderTypes;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidOrderType;

$requestFactory = new RequestFactory(
    'https://tranz-ware-payment-gateway/url',
    'E1000010',
    'https://your-site-address-here/samples/order_approved.php',
    'https://your-site-address-here/samples/order_declined.php',
    'https://your-site-address-here/samples/order_canceled.php',
    'EN'
);
$keyFile = __DIR__ . '/certificates/your-private-key.pem';
$keyPass = file_get_contents(__DIR__ . '/certificates/your-private-key-pass.txt');
$certFile = __DIR__ . '/certificates/cert-signed-by-payment-gateway-part.crt';
try {
    $requestFactory
        ->setCertificate($certFile, $keyFile, $keyPass)
        ->enableSSLVerification()
        ->setDebugFile(__DIR__ . '/debug.log');

    $orderRequest = $requestFactory->createOrderRequest(
        1,
        CurrencyCodes::USD,
        'TEST PAYMENT $0.01',
        OrderTypes::PURCHASE
    );
    /**
     * Or shorthands:
     * $orderRequest = $requestFactory->createOrderRequest(1, CurrencyCodes::USD, 'TEST PAYMENT $0.01');
     * $orderRequest = $requestFactory->createOrderPurchaseRequest(1, CurrencyCodes::USD, 'TEST PAYMENT $0.01');
     */
    $orderRequestResult = $orderRequest->execute();

    if ($orderRequestResult->getHttpStatus() === 200 && $orderRequestResult->success()) {
        $orderData = $orderRequestResult->getData();
        echo '<a href="' . htmlspecialchars($orderData->paymentUrl, ENT_QUOTES, 'UTF-8') . '">LINK TO PAYMENT</a>';
        exit(0);
    }

    throw new RuntimeException('Gateway rejected order creation.');
} catch (InvalidOrderType $e) {
    http_response_code(400);
    exit($e->getMessage());
} catch (Exception $e) {
    http_response_code(502);
    exit($e->getMessage());
}
