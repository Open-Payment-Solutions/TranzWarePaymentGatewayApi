<?php
require_once('vendor/autoload.php');

use \OpenPaymentSolutions\TranzWarePaymentGateway\HandlerFactory;

$handlerFactory = new HandlerFactory();
$orderCallbackHandler = $handlerFactory->createOrderCallbackHandler();

$orderStatusData = $orderCallbackHandler->handle();

var_dump($orderStatusData);
