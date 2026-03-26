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

namespace Magento\AdobeCommerceWebhooks\Model\Filter;

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextRetrieverException;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextRetriever as Retriever;
use Magento\AdobeCommerceWebhooks\Model\DataConverter\ArgumentDataConverterInterface;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Retrieves a value from the application context.
 */
class ContextRetriever
{
    /**
     * @param Retriever $retriever
     * @param ArgumentDataConverterInterface $argumentDataConverter
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Retriever $retriever,
        private readonly ArgumentDataConverterInterface $argumentDataConverter,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Retrieves a value from a supported application context based on the provided source string.
     *
     * Logs error message for the given hook if retrieval or conversion fails and returns null.
     *
     * @param string $source
     * @param Hook $hook
     * @return array|mixed|null
     */
    public function getContextValue(string $source, Hook $hook)
    {
        try {
            $contextEntity = $this->retriever->getContextValue($source);
        } catch (ContextRetrieverException $e) {
            $this->logError($e->getMessage(), $hook);
            return null;
        }

        try {
            if (is_object($contextEntity)) {
                $contextEntity = $this->convertObject($contextEntity);
            } elseif (is_array($contextEntity)) {
                $contextEntity = $this->convertArray($contextEntity);
            }
        } catch (Throwable $e) {
            $this->logError(
                sprintf(
                    'Context access with source \'%s\' can not be converted.',
                    $source
                ),
                $hook
            );
            return null;
        }

        return $contextEntity;
    }

    /**
     * Converts an array, converting any object elements to their array representation.
     *
     * @param array $items
     * @return array
     */
    private function convertArray(array $items): array
    {
        return array_map(function ($item) {
            if (is_object($item)) {
                return $this->convertObject($item);
            }
            return $item;
        }, $items);
    }

    /**
     * Converts an object to an array representation.
     *
     * @param object $contextEntity
     * @return array
     */
    private function convertObject(object $contextEntity): array
    {
        $convertedData = $this->argumentDataConverter->convert([$contextEntity]);
        $result = $convertedData[0] ?? [];

        return is_array($result) ? $result : [];
    }

    /**
     * Logs an error message.
     *
     * @param string $message
     * @param Hook $hook
     */
    private function logError(string $message, Hook $hook): void
    {
        $this->logger->error(
            $message,
            [
                'hook' => $hook,
                'destination' => ['internal', 'external']
            ]
        );
    }
}
