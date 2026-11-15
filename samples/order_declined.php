<?php

/**
 * Parse the decline callback and handle invalid callback data.
 */

$autoloadFile = __DIR__ . '/vendor/autoload.php';
require_once is_file($autoloadFile) ? $autoloadFile : __DIR__ . '/../vendor/autoload.php';

use OpenPaymentSolutions\TranzWarePaymentGateway\HandlerFactory;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\EmptyXMLMessage;
use OpenPaymentSolutions\TranzWarePaymentGateway\Exceptions\InvalidXMLMessage;

$handlerFactory = new HandlerFactory();
$orderCallbackHandler = $handlerFactory->createOrderCallbackHandler();

try {
    $orderStatusData = $orderCallbackHandler->handle();

    var_dump($orderStatusData);
} catch (EmptyXMLMessage $e) {
    http_response_code(400);
    exit($e->getMessage());
} catch (InvalidXMLMessage $e) {
    http_response_code(400);
    exit($e->getMessage());
}
