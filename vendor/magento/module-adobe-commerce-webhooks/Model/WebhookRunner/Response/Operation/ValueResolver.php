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

namespace Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\Operation;

use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\OperationException;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\OperationInterface;
use Magento\Framework\ObjectManagerInterface;
use Throwable;

/**
 * Resolves value from the webhook response
 */
class ValueResolver
{
    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(private ObjectManagerInterface $objectManager)
    {
    }

    /**
     * Resolves value from the webhook response.
     *
     * If instance is provided in the configuration create a new object with value as arguments/
     *
     * @param array $configuration
     * @return mixed
     * @throws OperationException
     */
    public function resolve(array $configuration)
    {
        if (!isset($configuration[OperationInterface::INSTANCE])) {
            return $configuration[OperationInterface::VALUE] ?: null;
        }

        try {
            return $this->objectManager->create(
                $configuration[OperationInterface::INSTANCE],
                $configuration[OperationInterface::VALUE] ?: []
            );
        } catch (Throwable $e) {
            throw new OperationException(
                __(
                    'Failed to create an instance of %1: %2',
                    $configuration[OperationInterface::INSTANCE],
                    $e->getMessage()
                )
            );
        }
    }
}
