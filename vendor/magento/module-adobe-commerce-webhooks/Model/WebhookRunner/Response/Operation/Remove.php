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

use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\OperationException;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\OperationInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Operation for removing data from arguments given the configuration in webhook response
 */
class Remove implements OperationInterface
{
    private const UNSET_METHOD = 'unsetData';

    /**
     * @param Hook $hook
     * @param array $configuration
     * @param DataUpdater $dataUpdater
     * @param LoggerInterface $logger
     */
    public function __construct(
        private Hook $hook,
        private array $configuration,
        private DataUpdater $dataUpdater,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Removes a value from the arguments based on the provided configuration in the webhook response.
     *
     * @param array $arguments
     * @return void
     * @throws OperationException
     */
    public function execute(array &$arguments): void
    {
        $pathParts = explode('/', $this->configuration[self::PATH]);

        try {
            $this->dataUpdater->updateValueByPath($arguments, $pathParts, [$this, 'removeValue']);
        } catch (DataUpdaterException $e) {
            $this->logDebugMessage($e->getMessage());
        }
    }

    /**
     * Removes value from the provided argument.
     *
     * If the argument is an array and a value exists at the provided path, the value is unset.
     * If the argument is an object, an attempt is made to call an unset data method with the provided path.
     *
     * @param mixed $argumentToUpdate
     * @param string $path
     * @return void
     * @throws OperationException
     */
    public function removeValue(&$argumentToUpdate, string $path): void
    {
        try {
            if (is_object($argumentToUpdate)) {
                $argumentToUpdate->{self::UNSET_METHOD}($path);
            } elseif (is_array($argumentToUpdate) && array_key_exists($path, $argumentToUpdate)) {
                unset($argumentToUpdate[$path]);
            } else {
                $this->logDebugMessage();
            }
        } catch (Throwable $e) {
            throw new OperationException(
                __(
                    'Unable to remove a value with path "%1" for hook "%2"',
                    $this->configuration[self::PATH],
                    $this->getHook()->getName()
                )
            );
        }
    }

    /**
     * Logs debug message, appends error message if provided
     *
     * @param string|null $error
     * @return void
     */
    private function logDebugMessage(?string $error = null): void
    {
        $message = sprintf(
            'The webhook operation was unable to remove a value with path "%s" for hook "%s"',
            $this->configuration[self::PATH],
            $this->getHook()->getName()
        );

        if (!empty($error)) {
            $message .= ': ' . $error;
        }

        $this->logger->debug($message, ['hook' => $this->getHook()]);
    }

    /**
     * @inheritDoc
     */
    public function getHook(): Hook
    {
        return $this->hook;
    }
}
