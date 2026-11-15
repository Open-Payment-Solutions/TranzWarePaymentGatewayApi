<?php

namespace tests;

use OpenPaymentSolutions\TranzWarePaymentGateway\CurrencyCodes;
use OpenPaymentSolutions\TranzWarePaymentGateway\Urls;
use OpenPaymentSolutions\TranzWarePaymentGateway\UrlProviderInterface;
use PHPUnit\Framework\TestCase;

final class UrlsTest extends TestCase
{
    /**
     * Keep unconfigured URLs empty.
     *
     * @return void
     */
    public function testUrlsStartEmpty(): void
    {
        $urls = new Urls();
        $this->assertSame('', $urls->getGatewayUrl());
        $this->assertSame('', $urls->getOnOrderApprovedUrl());
        $this->assertSame('', $urls->getOnOrderDeclinedUrl());
        $this->assertSame('', $urls->getOnOrderCanceledUrl());
    }

    /**
     * Allow URL setters to be chained and updated independently.
     *
     * @return void
     */
    public function testUrlsCanBeChainedAndUpdatedIndependently(): void
    {
        $urls = new Urls();
        $this->assertInstanceOf(UrlProviderInterface::class, $urls);
        $this->assertSame($urls, $urls->setGatewayUrl('https://gateway.example'));
        $this->assertSame($urls, $urls->setOnOrderApprovedUrl('https://shop.example/approved'));
        $this->assertSame($urls, $urls->setOnOrderDeclinedUrl('https://shop.example/declined'));
        $this->assertSame($urls, $urls->setOnOrderCanceledUrl('https://shop.example/canceled'));
        $urls->setGatewayUrl('https://new.example');
        $this->assertSame('https://new.example', $urls->getGatewayUrl());
        $this->assertSame('https://shop.example/approved', $urls->getOnOrderApprovedUrl());
        $this->assertSame('https://shop.example/declined', $urls->getOnOrderDeclinedUrl());
        $this->assertSame('https://shop.example/canceled', $urls->getOnOrderCanceledUrl());
    }

    /**
     * Match currency enum values to gateway codes.
     *
     * @return void
     */
    public function testCurrencyCodesMatchGatewayValues(): void
    {
        $this->assertSame(['840', '978', '944', '643', '938'], [
            CurrencyCodes::USD->value,
            CurrencyCodes::EUR->value,
            CurrencyCodes::AZN->value,
            CurrencyCodes::RUB->value,
            CurrencyCodes::SDG->value,
        ]);
    }
}
