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

namespace Magento\AdobeCommerceWebhooks\Model\WebhookInfo;

use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Collector\AggregatedEventListInterface;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Collector\ObserverEventsCollector\EventPrefixesCollector;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\Collector\ApiServiceCollector;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\Collector\CollectorException;
use Magento\AdobeCommerceOutOfProcessExtensibility\Model\Generator\Collector\ResourceModelCollector;
use Magento\AdobeCommerceWebhooks\Model\Webhook;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;

/**
 * Collects argument data based on webhook name and type
 */
class ArgumentCollector implements ArgumentCollectorInterface
{
    public const PARAM_NAME = 'name';
    public const PARAM_TYPE = 'type';

    /**
     * @param ApiServiceCollector $apiServiceCollector
     * @param ResourceModelCollector $resourceModelCollector
     * @param AggregatedEventListInterface $aggregatedEventList
     */
    public function __construct(
        private ApiServiceCollector $apiServiceCollector,
        private ResourceModelCollector $resourceModelCollector,
        private AggregatedEventListInterface $aggregatedEventList
    ) {
    }

    /**
     * Collects argument data based on webhook name and type.
     *
     * @param Webhook $webhook
     * @return array
     * @throws LocalizedException
     */
    public function collect(Webhook $webhook): array
    {
        $webhookName = $webhook->getName();
        $methodNameParts = explode('.', $webhookName);
        if (!in_array($methodNameParts[0], [Webhook::WEBHOOK_PLUGIN, Webhook::WEBHOOK_OBSERVER])) {
            throw new LocalizedException(
                __(
                    'Wrong webhook prefix. Supported webhooks must have names starting with [%1]',
                    implode(', ', [Webhook::WEBHOOK_PLUGIN, Webhook::WEBHOOK_OBSERVER])
                )
            );
        }

        try {
            if ($methodNameParts[0] === Webhook::WEBHOOK_PLUGIN) {
                $params = $this->collectParamsForPluginType($webhook);
            } else {
                $params = $this->collectParamsForObserverType($webhook);
            }
        } catch (CollectorException $e) {
            throw new LocalizedException(
                __('Can\'t get info about webhook %1. Error: %2', $webhookName, $e->getMessage())
            );
        }

        if ($webhook->getType() === Webhook::TYPE_AFTER) {
            $params[] = [
                self::PARAM_NAME => 'result',
                self::PARAM_TYPE => 'mixed',
            ];
        }

        return $params;
    }

    /**
     * Collects params for plugin type webhook based on collected plugins data.
     *
     * @param Webhook $webhook
     * @return array
     * @throws CollectorException
     */
    private function collectParamsForPluginType(Webhook $webhook): array
    {
        $webhookName = $webhook->getName();
        if (str_contains($webhookName, 'resource_model')) {
            $resourceModel = $this->resourceModelCollector->collect($webhookName);
            $subjectParam = [
                self::PARAM_TYPE => array_key_first($resourceModel),
                self::PARAM_NAME => 'subject'
            ];
            $resourceModelData = reset($resourceModel);
            $params = array_merge([$subjectParam], $resourceModelData[0]['params'] ?? []);
            foreach ($params as &$param) {
                if ($param[self::PARAM_NAME] === 'object'
                    && $param[self::PARAM_TYPE] === AbstractModel::class
                ) {
                    $param[self::PARAM_TYPE] = str_replace('\ResourceModel', '', array_key_first($resourceModel));
                }
            }

        } else {
            $apiInterface = $this->apiServiceCollector->collect($webhookName);
            $subjectParam = [
                self::PARAM_TYPE => array_key_first($apiInterface),
                self::PARAM_NAME => 'subject'
            ];
            $apiInterfaceData = reset($apiInterface);
            $params = array_merge([$subjectParam], $apiInterfaceData[0]['params'] ?? []);
        }

        return $params;
    }

    /**
     * Collects params for observer webhook based on collected observer data.
     *
     * @param Webhook $webhook
     * @return array
     * @throws LocalizedException
     */
    private function collectParamsForObserverType(Webhook $webhook): array
    {
        $params = [];
        $webhookName = $webhook->getName();
        $eventList = $this->aggregatedEventList->getList();
        if (!isset($eventList[$webhookName])) {
            throw new LocalizedException(__('Cannot get details about webhook %1', $webhookName));
        }

        $className = $eventList[$webhookName]->getEventClassEmitter();
        $params[] = [
            self::PARAM_NAME => 'eventName',
            self::PARAM_TYPE => 'string',
        ];

        if (is_a($className, AbstractModel::class, true) && $this->hasAbstractModelEventSuffix($webhookName)) {
            $params['data'][] = [
                self::PARAM_NAME => 'data_object',
                self::PARAM_TYPE => $className,
            ];
        }

        return $params;
    }

    /**
     * Checks if the event name ends with one of the abstract model event suffixes.
     *
     * @param string $eventName
     * @return bool
     */
    private function hasAbstractModelEventSuffix(string $eventName): bool
    {
        foreach (EventPrefixesCollector::ABSTRACT_MODEL_EVENTS as $eventSuffix) {
            if (str_ends_with($eventName, $eventSuffix)) {
                return true;
            }
        }
        return false;
    }
}
