<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2026 Adobe
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

namespace Magento\AdobeCommerceEventsClient\Event\Context;

use Magento\AdobeCommerceEventsClient\Event\Converter\EventDataConverter;
use Magento\AdobeCommerceEventsClient\Event\Event;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextRetriever as Retriever;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Context\ContextRetrieverException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Retrieves a value from the application context.
 */
class ContextRetriever
{
    /**
     * @param Retriever $retriever
     * @param EventDataConverter $eventDataConverter
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Retriever $retriever,
        private readonly EventDataConverter $eventDataConverter,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Retrieves a value from a supported application context based on the provided source string.
     *
     * Logs error message for the given event if retrieval or conversion fails and returns null.
     *
     * @param string $source
     * @param Event $event
     * @return array|mixed|null
     */
    public function getContextValue(string $source, Event $event)
    {
        try {
            $contextEntity = $this->retriever->getContextValue($source);
        } catch (ContextRetrieverException $e) {
            $this->logWarning($e->getMessage(), $event);
            return null;
        }

        if (is_object($contextEntity)) {
            try {
                $contextEntity = $this->eventDataConverter->convert($contextEntity);
            } catch (Throwable $e) {
                $this->logWarning(
                    sprintf(
                        'Can not convert context value retrieved with source \'%s\'.',
                        $source
                    ),
                    $event
                );
                return null;
            }
        }

        return $contextEntity;
    }

    /**
     * Logs a warning message for the given event if context retrieval fails.
     *
     * @param string $message
     * @param Event $event
     * @return void
     */
    private function logWarning(string $message, Event $event): void
    {
        $this->logger->warning(
            sprintf('Failed to retrieve context value for event %s: %s', $event->getName(), $message),
            [
                'destination' => ['internal', 'external']
            ]
        );
    }
}
