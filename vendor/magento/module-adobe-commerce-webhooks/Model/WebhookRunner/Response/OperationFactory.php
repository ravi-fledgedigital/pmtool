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

namespace Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response;

use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\Framework\Exception\InvalidArgumentException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\ObjectManagerInterface;

/**
 * Creates an instance of OperationInterface
 */
class OperationFactory
{
    private const REQUIRED_FIELDS = 'requiredFields';

    /**
     * @param ObjectManagerInterface $objectManager
     * @param array $operations
     */
    public function __construct(
        private ObjectManagerInterface $objectManager,
        private array $operations = []
    ) {
    }

    /**
     * Creates an instance of OperationInterface based on provided name.
     *
     * Throws an exception if such an operation is not registered.
     *
     * @param string $name
     * @param Hook $hook
     * @param array $configuration
     * @return OperationInterface
     * @throws NotFoundException|InvalidArgumentException
     */
    public function create(string $name, Hook $hook, array $configuration): OperationInterface
    {
        if (!isset($this->operations[$name])) {
            throw new NotFoundException(__('Operation %1 is not registered', $name));
        }

        if (!empty($this->operations[$name][self::REQUIRED_FIELDS])) {
            $this->validateRequiredFields($this->operations[$name][self::REQUIRED_FIELDS], $configuration);
        }

        return $this->objectManager->create($this->operations[$name]['class'], [
            'hook' => $hook,
            'configuration' => $configuration
        ]);
    }

    /**
     * Validates that configuration contains all required fields for the operation to be created.
     *
     * @param array $requiredFields
     * @param array $configuration
     * @throws InvalidArgumentException
     */
    private function validateRequiredFields(array $requiredFields, array $configuration): void
    {
        $missedRequiredFields = array_diff_key(array_flip($requiredFields), $configuration);

        if (!empty($missedRequiredFields)) {
            throw new InvalidArgumentException(__(
                'The required parameters are missed in the webhook response: %1',
                implode(', ', $missedRequiredFields)
            ));
        }
    }
}
