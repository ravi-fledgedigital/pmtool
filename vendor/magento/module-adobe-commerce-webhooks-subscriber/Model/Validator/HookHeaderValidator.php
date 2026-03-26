<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2025 Adobe
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
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooksSubscriber\Model\Validator;

use Magento\AdobeCommerceWebhooks\Api\Data\HookHeaderInterface;
use Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface;
use Magento\AdobeCommerceWebhooks\Model\Config\WebhookConfigurationException;
use Magento\AdobeCommerceWebhooksSubscriber\Api\Data\HookInterface;
use Magento\Framework\Exception\ValidatorException;

/**
 * Validates the hook header
 */
class HookHeaderValidator implements HookDataValidatorInterface
{
    /**
     * Validates the hook headers
     *
     * @param HookInterface $hook
     * @return void
     * @throws ValidatorException
     */
    public function validate(HookInterface $hook): void
    {
        try {
            $headers = $hook->getHookData()[WebhookDataInterface::HEADERS] ?? [];
            if (!is_array($headers) || empty($headers)) {
                return;
            }

            foreach ($headers as $header) {
                if (!is_array($header)) {
                    continue;
                }

                $this->validateHeader($header);
            }
        } catch (WebhookConfigurationException $e) {
            throw new ValidatorException(__(
                'The webhook data has an invalid format. ' . $e->getMessage()
            ));
        }
    }

    /**
     * Validates a single header
     *
     * @param array $header
     * @return void
     * @throws ValidatorException
     */
    private function validateHeader(array $header): void
    {
        $requiredFields = [
            HookHeaderInterface::NAME,
            HookHeaderInterface::VALUE,
        ];

        foreach ($requiredFields as $requiredField) {
            if (empty($header[$requiredField])) {
                throw new ValidatorException(
                    __('Header field "%1" is required and can not be empty.', $requiredField)
                );
            }
        }
    }
}
