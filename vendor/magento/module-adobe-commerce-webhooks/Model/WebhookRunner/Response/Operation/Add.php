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

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Util\CaseConverter;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\OperationException;
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\OperationInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Operation for updating the result by adding new elements based on the configuration from the endpoint
 */
class Add implements OperationInterface
{
    /**
     * @param Hook $hook
     * @param CaseConverter $caseConverter
     * @param array $configuration
     * @param ValueResolver $valueResolver
     * @param DataUpdater $dataUpdater
     * @param LoggerInterface $logger
     */
    public function __construct(
        private Hook $hook,
        private CaseConverter $caseConverter,
        private array $configuration,
        private ValueResolver $valueResolver,
        private DataUpdater $dataUpdater,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getHook(): Hook
    {
        return $this->hook;
    }

    /**
     * Adds value to the arguments based on provided configuration in webhook response.
     *
     * @param array $arguments
     * @return void
     * @throws OperationException
     */
    public function execute(array &$arguments): void
    {
        $pathParts = explode('/', $this->configuration[self::PATH]);

        try {
            $this->dataUpdater->updateValueByPath($arguments, $pathParts, [$this, 'addValue']);
        } catch (DataUpdaterException $e) {
            $this->logDebugMessage($e->getMessage());
        }
    }

    /**
     * Adds a value from the configuration to the provided argument.
     *
     * If an argument by a given path exists and its array, appends value to the array.
     * If the argument is an object:
     *   - try to call Get method for the path
     *   - if the result of the Get method is an array append a new value to that array and call set method with it
     *   - if the result of the Get method is not an array just call set method with provided value
     * Otherwise, treat the argument as an array and try to add value to the $path index.
     *
     * @param mixed $argumentToUpdate
     * @param string $path
     * @return void
     * @throws OperationException
     */
    public function addValue(&$argumentToUpdate, string $path): void
    {
        $value = $this->valueResolver->resolve($this->configuration);

        try {
            if (is_object($argumentToUpdate)) {
                $methodName = $this->caseConverter->snakeCaseToCamelCase($path);
                $getMethod = 'get' . $methodName;
                $setMethod = 'set' . $methodName;
                $currentValue = $argumentToUpdate->$getMethod();
                if (is_array($currentValue)) {
                    $currentValue[] = $value;
                    $argumentToUpdate->$setMethod($currentValue);
                } else {
                    $argumentToUpdate->$setMethod($value);
                }
            } elseif (isset($argumentToUpdate[$path]) && is_array($argumentToUpdate[$path])) {
                $argumentToUpdate[$path][] = $value;
            } elseif (is_array($argumentToUpdate) && !isset($argumentToUpdate[$path])) {
                $argumentToUpdate[$path] = $value;
            } else {
                $this->logDebugMessage();
            }
        } catch (Throwable $e) {
            throw new OperationException(
                __(
                    'Unable to add the value by path "%1" for hook "%2"',
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
            'The webhook operation was unable to add a value by path "%s" for hook "%s"',
            $this->configuration[self::PATH],
            $this->getHook()->getName()
        );

        if (!empty($error)) {
            $message .= ': ' . $error;
        }

        $this->logger->debug($message, ['hook' => $this->getHook()]);
    }
}
