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

namespace Magento\AdobeCommerceWebhooks\Model;

use Magento\AdobeCommerceWebhooks\Model\Webhook\Hook;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookField;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookHeader;
use Magento\AdobeCommerceWebhooks\Model\Webhook\Batch;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookRule;
use Magento\Framework\ObjectManagerInterface;

/**
 * Creates a webhook instance based on the provided configuration.
 */
class WebhookFactory
{
    /**
     * Factory constructor
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(private ObjectManagerInterface $objectManager)
    {
    }

    /**
     * Creates Webhook instance based on the provided configuration.
     *
     * @param array $webhookData
     * @return Webhook
     */
    public function create(array $webhookData = []): Webhook
    {
        $webhook = $this->objectManager->create(Webhook::class, ['data' => $webhookData]);
        $webhook->setData(Webhook::BATCHES, $this->createBatches($webhookData, $webhook));

        return $webhook;
    }

    /**
     * Creates an array of Batch objects sorted by increasing order.
     *
     * @param array $webhookData
     * @param Webhook $webhook
     * @return Batch[]
     */
    private function createBatches(array $webhookData, Webhook $webhook): array
    {
        $batches = [];

        foreach ($webhookData[Webhook::BATCHES] ?? [] as $batchData) {
            $batchData[Batch::WEBHOOK] = $webhook;
            $batch = $this->objectManager->create(Batch::class, ['data' => $batchData]);
            $batch->setData(Batch::HOOKS, $this->createHooks($batchData, $batch));

            $batches[] = $batch;
        }

        usort($batches, function (Batch $batchA, Batch $batchB) {
            return $batchA->getOrder() <=> $batchB->getOrder();
        });

        return $batches;
    }

    /**
     * Creates an array of Hook objects.
     *
     * @param array $batchData
     * @param Batch $batch
     * @return Hook[]
     */
    private function createHooks(array $batchData, Batch $batch): array
    {
        $hooks = [];

        foreach ($batchData[Batch::HOOKS] ?? [] as $hookData) {
            $hookData[Hook::BATCH] = $batch;
            $hook = $this->objectManager->create(Hook::class, ['data' => $hookData]);
            $hook->setData(Hook::FIELDS, $this->createHookFields($hookData, $hook));
            $hook->setData(Hook::HEADERS, $this->createHookHeaders($hookData, $hook));
            $hook->setData(Hook::RULES, $this->createHookRules($hookData, $hook));
            $hooks[] = $hook;
        }

        return $hooks;
    }

    /**
     * Creates an array of HookHeader objects.
     *
     * @param mixed $hookData
     * @param Hook $hook
     * @return HookHeader[]
     */
    private function createHookHeaders(mixed $hookData, Hook $hook): array
    {
        return array_map(
            function ($hookHeaderData) use ($hook) {
                $hookHeaderData[HookHeader::HOOK] = $hook;
                return $this->objectManager->create(HookHeader::class, ['data' => $hookHeaderData]);
            },
            $hookData[Hook::HEADERS] ?? []
        );
    }

    /**
     * Creates an array of HookField objects.
     *
     * @param mixed $hookData
     * @param Hook $hook
     * @return HookField[]
     */
    private function createHookFields(mixed $hookData, Hook $hook): array
    {
        return array_map(
            function ($hookFieldData) use ($hook) {
                $hookFieldData[HookField::HOOK] = $hook;
                return $this->objectManager->create(HookField::class, ['data' => $hookFieldData]);
            },
            $hookData[Hook::FIELDS] ?? []
        );
    }

    /**
     * Creates an array of HookRule objects.
     *
     * @param mixed $hookData
     * @param Hook $hook
     * @return HookRule[]
     */
    private function createHookRules(mixed $hookData, Hook $hook): array
    {
        return array_map(
            function ($hookRuleData) use ($hook) {
                $hookRuleData[HookRule::HOOK] = $hook;
                return $this->objectManager->create(HookRule::class, ['data' => $hookRuleData]);
            },
            $hookData[Hook::RULES] ?? []
        );
    }
}
