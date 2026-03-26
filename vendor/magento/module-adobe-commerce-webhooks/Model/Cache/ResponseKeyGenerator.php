<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooks\Model\Cache;

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Request\RequestIdInterface;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Request\RequestParams;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Generates keys for caching hook responses by hashing hook request parameters.
 */
class ResponseKeyGenerator implements KeyGeneratorInterface
{
    /**
     * @param EncryptorInterface $encryptor
     * @param Json $json
     * @param array $filteredHeaders
     */
    public function __construct(
        private EncryptorInterface $encryptor,
        private Json $json,
        private array $filteredHeaders = [RequestIdInterface::REQUEST_ID_HEADER]
    ) {
    }

    /**
     * @inheritDoc
     */
    public function generate(RequestParams $requestParams): string
    {
        $headers = array_diff_key($requestParams->getHeaders(), array_flip($this->filteredHeaders));

        $keyParts = [
            $requestParams->getUrl(),
            $this->json->serialize($headers),
            $this->json->serialize($requestParams->getBody())
        ];
        return $this->encryptor->hash(implode('_', $keyParts));
    }
}
