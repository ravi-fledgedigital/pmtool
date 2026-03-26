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
use Magento\AdobeCommerceWebhooks\Model\WebhookRunner\Response\OperationException;

/**
 * Updates nested data structure by the provided path
 */
class DataUpdater
{
    /**
     * @param CaseConverter $caseConverter
     */
    public function __construct(
        private CaseConverter $caseConverter,
    ) {
    }

    /**
     * Updates nested data structure by the provided path by applying a callback to the updated element
     *
     * @param array $argumentsToUpdate
     * @param array $pathParts
     * @param callable $valueProcessor
     * @param array|null $arguments
     * @return void
     * @throws OperationException
     * @throws DataUpdaterException
     */
    public function updateValueByPath(
        array &$argumentsToUpdate,
        array $pathParts,
        callable $valueProcessor,
        ?array $arguments = null
    ): void {
        $pathPartsCount = count($pathParts);

        for ($i = 0; $i < $pathPartsCount; $i++) {
            $currentPart = $pathParts[$i];
            if ($i === $pathPartsCount - 1) {
                $valueProcessor($argumentsToUpdate, $currentPart, $arguments);
                return;
            }

            if (is_object($argumentsToUpdate)) {
                $this->setObjectValue($argumentsToUpdate, array_slice($pathParts, $i), $valueProcessor, $arguments);
                return;
            }

            if (!is_array($argumentsToUpdate) || !array_key_exists($currentPart, $argumentsToUpdate)) {
                throw new DataUpdaterException(__('The path part "%1" does not exist.', $currentPart));
            }

            $argumentsToUpdate = &$argumentsToUpdate[$currentPart];
        }
    }

    /**
     * Set nested value for the object.
     *
     * @param object $argumentsToUpdate
     * @param array $pathParts
     * @param callable $valueProcessor
     * @param array|null $arguments
     * @return void
     * @throws OperationException
     * @throws DataUpdaterException
     */
    private function setObjectValue(
        object $argumentsToUpdate,
        array $pathParts,
        callable $valueProcessor,
        ?array $arguments = null
    ): void {
        $pathPartsCount = count($pathParts);

        for ($i = 0; $i < $pathPartsCount; $i++) {
            $currentPart = $pathParts[$i];
            if ($i === $pathPartsCount - 1) {
                $valueProcessor($argumentsToUpdate, $currentPart, $arguments);
                return;
            }

            $method = 'get' . $this->caseConverter->snakeCaseToCamelCase($currentPart);
            $tempArguments = $argumentsToUpdate->$method();

            if (is_array($tempArguments)) {
                $method = 'set' . $this->caseConverter->snakeCaseToCamelCase($currentPart);
                $this->updateValueByPath(
                    $tempArguments,
                    array_slice($pathParts, $i + 1),
                    $valueProcessor,
                    $arguments
                );
                $argumentsToUpdate->$method($tempArguments);
                return;
            }

            $argumentsToUpdate = $tempArguments;
        }
    }
}
