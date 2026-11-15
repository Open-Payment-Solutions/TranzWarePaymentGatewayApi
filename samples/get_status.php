<?php

$autoloadFile = __DIR__ . '/vendor/autoload.php';
require_once is_file($autoloadFile) ? $autoloadFile : __DIR__ . '/../vendor/autoload.php';

use OpenPaymentSolutions\TranzWarePaymentGateway\RequestFactory;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidOrderId;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidSessionId;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\OrderStatusRetrieveException;

$orderId = $_GET['ORDERID'] ?? null;
$sessionId = $_GET['SESSIONID'] ?? null;
if (($orderId !== null && !is_string($orderId)) || ($sessionId !== null && !is_string($sessionId))) {
    http_response_code(400);
    exit('Missing or invalid order identifiers.');
}

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

    $orderStatusRequest = $requestFactory->createOrderStatusRequest($orderId, $sessionId);
    $orderStatusRequestResult = $orderStatusRequest->execute();
    $orderStatusData = $orderStatusRequestResult->getData();

    var_dump($orderStatusData);
} catch (InvalidOrderId | InvalidSessionId $e) {
    http_response_code(400);
    exit($e->getMessage());
} catch (OrderStatusRetrieveException $e) {
    http_response_code(502);
    exit($e->getMessage());
}
