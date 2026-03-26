<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2024 Adobe
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

namespace Magento\AdobeCommerceWebhooks\Model\Signature;

use Magento\AdobeCommerceWebhooks\Model\Config\System\Config;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\Serializer\Base64Json;

/**
 * @inheritDoc
 */
class DigitalSignature implements DigitalSignatureInterface
{
    /**
     * @param EncryptorInterface $encryptor
     * @param Config $config
     * @param Base64Json $base64Json
     */
    public function __construct(
        private EncryptorInterface $encryptor,
        private Config $config,
        private Base64Json $base64Json,
    ) {
    }

    /**
     * Signs the webhook payload with the digital signature using the private key and openssl_sign function
     *
     * @param array $hookPayload
     * @return string
     */
    public function sign(array $hookPayload): string
    {
        $privateKey = $this->encryptor->decrypt($this->config->getDigitalSignaturePrivateKey());

        openssl_sign(
            $this->base64Json->serialize($hookPayload),
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );

        return base64_encode($signature);
    }
}
