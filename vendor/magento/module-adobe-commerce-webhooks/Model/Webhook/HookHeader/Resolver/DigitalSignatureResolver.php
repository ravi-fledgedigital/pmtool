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

namespace Magento\AdobeCommerceWebhooks\Model\Webhook\HookHeader\Resolver;

use Magento\AdobeCommerceWebhooks\Model\Config\System\Config;
use Magento\AdobeCommerceWebhooks\Model\Signature\DigitalSignatureInterface;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookHeader\HookHeaderResolverInterface;
use Magento\Framework\Exception\InvalidArgumentException;

/**
 * Returns a signature header
 */
class DigitalSignatureResolver implements HookHeaderResolverInterface
{
    public const DIGITAL_SIGNATURE_HEADER = 'x-adobe-commerce-webhook-signature';

    /**
     * @param Config $config
     * @param DigitalSignatureInterface $digitalSignature
     */
    public function __construct(
        private Config $config,
        private DigitalSignatureInterface $digitalSignature,
    ) {
    }

    /**
     * Returns a signature header if digital signature is enabled.
     *
     * @param Hook $hook
     * @param array $hookData
     * @return array
     * @throws InvalidArgumentException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(Hook $hook, array $hookData): array
    {
        $headers = [];

        if ($this->config->isDigitalSignatureEnabled() && !empty($this->config->getDigitalSignaturePrivateKey())) {
            $headers[self::DIGITAL_SIGNATURE_HEADER] = $this->digitalSignature->sign($hookData);
        }

        return $headers;
    }
}
