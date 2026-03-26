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

use Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface;
use Magento\AdobeCommerceWebhooks\Model\Config\WebhookConfigurationException;
use Magento\AdobeCommerceWebhooksSubscriber\Api\Data\HookInterface;
use Magento\Framework\Exception\ValidatorException;

/**
 * Validates the hook contains required fields and they are not empty
 */
class HookRequiredFieldsValidator implements HookDataValidatorInterface
{
    /**
     * List of fields that are required for the hook
     *
     * @var array
     */
    private const REQUIRED_FIELDS = [
        WebhookDataInterface::WEBHOOK_METHOD,
        WebhookDataInterface::WEBHOOK_TYPE,
        WebhookDataInterface::HOOK_NAME,
        WebhookDataInterface::BATCH_NAME,
    ];

    /**
     * List of fields that are required for the hook data array
     */
    private const REQUIRED_HOOK_DATA_FIELDS = [
        WebhookDataInterface::URL,
    ];

    /**
     * @param array $requiredFields
     * @param array $requiredHookDataFields
     */
    public function __construct(
        private readonly array $requiredFields = self::REQUIRED_FIELDS,
        private readonly array $requiredHookDataFields = self::REQUIRED_HOOK_DATA_FIELDS,
    ) {
    }

    /**
     * Validates the hook contains required fields and they are not empty
     *
     * @param HookInterface $hook
     * @return void
     * @throws ValidatorException
     */
    public function validate(HookInterface $hook): void
    {
        try {
            foreach ($this->requiredFields as $field) {
                if (empty($hook->getData($field))) {
                    throw new ValidatorException(
                        __('The field "%1" is required and can not be empty.', $field)
                    );
                }
            }

            $data = $hook->getHookData();
            foreach ($this->requiredHookDataFields as $field) {
                if (empty($data[$field])) {
                    throw new ValidatorException(
                        __('The field "%1" is required and can not be empty.', $field)
                    );
                }
            }
        } catch (WebhookConfigurationException $e) {
            throw new ValidatorException(__(
                'The webhook data has an invalid format. ' . $e->getMessage()
            ));
        }
    }
}
