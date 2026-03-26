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
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\OperationValueConverter;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Operation for modifying the result based on the configuration from endpoint
 */
class Replace implements OperationInterface
{
    /**
     * @param Hook $hook
     * @param CaseConverter $caseConverter
     * @param array $configuration
     * @param ValueResolver $valueResolver
     * @param DataUpdater $dataUpdater
     * @param OperationValueConverter $valueConverter
     * @param LoggerInterface $logger
     */
    public function __construct(
        private Hook $hook,
        private CaseConverter $caseConverter,
        private array $configuration,
        private ValueResolver $valueResolver,
        private DataUpdater $dataUpdater,
        private OperationValueConverter $valueConverter,
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
     * Replaces the value in the arguments based on provided configuration in webhook response.
     *
     * Do nothing if the value does not exist by provided path.
     *
     * @param array $arguments
     * @return void
     * @throws OperationException
     */
    public function execute(array &$arguments): void
    {
        $pathParts = explode('/', $this->configuration[self::PATH]);

        try {
            $this->dataUpdater->updateValueByPath($arguments, $pathParts, [$this, 'replaceValue'], $arguments);
        } catch (DataUpdaterException $e) {
            $this->logDebugMessage($e->getMessage());
        }
    }

    /**
     * Replaces a value in the provided element with a value from the configuration.
     *
     * If the argument is an object try to call the Set method for the path with value as argument.
     * Otherwise, treat the argument as an array and try to replace value for the $path index.
     *
     * @param mixed $argumentToUpdate
     * @param string $path
     * @param array|null $arguments
     * @return void
     * @throws OperationException
     */
    public function replaceValue(&$argumentToUpdate, string $path, ?array $arguments): void
    {
        $value = $this->valueConverter->convert(
            $this->valueResolver->resolve($this->configuration),
            $this->configuration[self::PATH],
            $this->hook->getFields(),
            $arguments
        );

        try {
            if (is_object($argumentToUpdate)) {
                $method = 'set' . $this->caseConverter->snakeCaseToCamelCase($path);
                $argumentToUpdate->$method($value);
            } elseif (isset($argumentToUpdate[$path]) &&
                is_array($argumentToUpdate[$path]) &&
                is_array($value)
            ) {
                $argumentToUpdate[$path] = array_replace_recursive($argumentToUpdate[$path], $value);
            } elseif (array_key_exists($path, $argumentToUpdate)) {
                if (is_object($argumentToUpdate[$path]) && is_array($value)) {
                    $this->replaceObjectValueArray($argumentToUpdate[$path], $value);
                } else {
                    $argumentToUpdate[$path] = $value;
                }
            } else {
                $this->logDebugMessage();
            }
        } catch (Throwable $e) {
            throw new OperationException(
                __(
                    'Unable to replace the value by path "%1" for hook "%2"',
                    $this->configuration[self::PATH],
                    $this->getHook()->getName()
                )
            );
        }
    }

    /**
     * Set array of values to the object by going through array and calling appropriate set methods based on key values
     *
     * @param object $object
     * @param array $fieldsToUpdate
     * @return void
     */
    private function replaceObjectValueArray(object $object, array $fieldsToUpdate): void
    {
        foreach ($fieldsToUpdate as $key => $value) {
            $method = 'set' . $this->caseConverter->snakeCaseToCamelCase($key);
            $object->$method($value);
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
            'The webhook operation was unable to replace a value by path "%s" for hook "%s"',
            $this->configuration[self::PATH],
            $this->getHook()->getName()
        );

        if (!empty($error)) {
            $message .= ': ' . $error;
        }

        $this->logger->debug($message, ['hook' => $this->getHook()]);
    }
}
