<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Model\Response;

use Magento\Framework\Serialize\SerializerInterface;
use Psr\Http\Message\ResponseInterface;

class GetToken
{
    private const KEY_TOKEN = 'access_token';
    private const KEY_EXPIRES_IN = 'expires_in';

    private SerializerInterface $serializer;
    private string $token;
    private int $expiresIn;

    public function __construct(
        SerializerInterface $serializer,
        ResponseInterface $response
    ) {
        $this->serializer = $serializer;
        $this->decodeResponse($response);
    }

    public function getAccessToken(): string
    {
        return $this->token;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    private function decodeResponse(ResponseInterface $response): void
    {
        $jsonBody = $this->serializer->unserialize($response->getBody());

        if (!isset($jsonBody[self::KEY_TOKEN]) || !isset($jsonBody[self::KEY_EXPIRES_IN])) {
            throw new \InvalidArgumentException('Invalid json data. Missing token or expiry field.');
        }

        $this->token = $jsonBody[self::KEY_TOKEN];
        $this->expiresIn = (int) $jsonBody[self::KEY_EXPIRES_IN];
    }
}
