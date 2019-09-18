<?php

require_once('vendor/autoload.php');

use \OpenPaymentSolutions\TranzWarePaymentGateway\TranzWarePaymentGatewayRequestFactory;

$requestFactory = new TranzWarePaymentGatewayRequestFactory(
    'https://tranz-ware-payment-gateway/url',
    'E1000010',
    'https://your-site-address-here/samples/order_approved.php',
    'https://your-site-address-here/samples/order_declined.php',
    'https://your-site-address-here/samples/order_canceled.php',
    'EN'
);
$keyFile = __DIR__.'/certificates/your-private-key.pem';
$keyPass = file_get_contents(__DIR__.'/certificates/your-private-key-pass.txt');
$certFile = __DIR__.'/certificates/cert-signed-by-payment-gateway-part.crt';
$requestFactory->setCertificate($certFile, $keyFile, $keyPass);
$requestFactory->setDebugFile(__DIR__.'/debug.log');

$orderStatusRequest = $requestFactory->createOrderStatusRequest($_GET['ORDERID'], $_GET['SESSIONID']);
$orderStatusRequestResult = $orderStatusRequest->execute();

$orderStatusData = $orderStatusRequestResult->getData();

var_dump($orderStatusData);
