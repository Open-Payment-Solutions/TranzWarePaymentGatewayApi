<?php
require_once('vendor/autoload.php');

use \OpenPaymentSolutions\TranzWarePaymentGateway\TranzWarePaymentGatewayHandlerFactory;

$handlerFactory = new TranzWarePaymentGatewayHandlerFactory();
$orderCallbackHandler = $handlerFactory->createOrderCallbackHandler();

$orderStatusData = $orderCallbackHandler->handle();

var_dump($orderStatusData);
