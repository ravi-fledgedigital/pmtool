<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\AepEventStreaming\Model;

use Firebase\JWT\JWT;
use Magento\Framework\Encryption\EncryptorInterface;
use Vaimo\AepEventStreaming\Api\ConfigInterface;

class JWTToken
{
    private const ALGORITHM = 'RS256';

    private EncryptorInterface $encryptor;
    private ConfigInterface $config;

    public function __construct(
        EncryptorInterface $encryptor,
        ConfigInterface $config
    ) {
        $this->encryptor = $encryptor;
        $this->config = $config;
    }

    public function get(): string
    {
        $privateKey = $this->encryptor->decrypt($this->config->getPrivateKey());

        return JWT::encode(
            $this->getJWTPayload(),
            $privateKey,
            self::ALGORITHM
        );
    }

    /**
     * @return string[]
     */
    private function getJWTPayload(): array
    {
        return [
            'exp' => \time() + $this->config->getJWTExpirationTime(),
            'iss' => $this->config->getOrganisationId(),
            'sub' => $this->config->getTechnicalAccountId(),
            'aud' => $this->config->getTokenAudience() . $this->encryptor->decrypt($this->config->getApiKey()),
            $this->config->getMetaScope() => true,
        ];
    }
}
