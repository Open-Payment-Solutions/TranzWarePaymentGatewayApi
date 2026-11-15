<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Data;

class HTTPInfo
{
    public readonly int $httpCode;
    public readonly string $url;
    public readonly ?string $contentType;
    public readonly float $totalTime;
    // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore -- Required public field name.
    public readonly array $__unparsed;

    /**
     * Normalize HTTP transport fields and retain other cURL information.
     *
     * @param array $info
     */
    public function __construct(array $info)
    {
        $this->httpCode = (int)($info['http_code'] ?? 0);
        $this->url = (string)($info['url'] ?? '');
        $this->contentType = $info['content_type'] ?? null;
        $this->totalTime = (float)($info['total_time'] ?? 0);
        $this->__unparsed = array_diff_key($info, array_flip(['http_code', 'url', 'content_type', 'total_time']));
    }
}
