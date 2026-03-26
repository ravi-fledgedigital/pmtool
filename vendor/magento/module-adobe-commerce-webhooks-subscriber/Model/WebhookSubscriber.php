<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2025 Adobe
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

namespace Magento\AdobeCommerceWebhooksSubscriber\Model;

use Magento\AdobeCommerceWebhooks\Api\Data\WebhookDataInterface;
use Magento\AdobeCommerceWebhooksSubscriber\Api\HookRepositoryInterface;
use Magento\AdobeCommerceWebhooksSubscriber\Api\WebhookSubscriberInterface;
use Magento\AdobeCommerceWebhooksSubscriber\Model\Data\WebhookDataToHookConverter;
use Magento\AdobeCommerceWebhooksSubscriber\Model\Validator\HookDataValidatorInterface;
use Magento\AdobeCommerceWebhooksSubscriber\Model\WebhookSubscriber\BatchOrderUpdaterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\ValidatorException;
use Psr\Log\LoggerInterface;

/**
 * @inheritDoc
 */
class WebhookSubscriber implements WebhookSubscriberInterface
{
    /**
     * @param HookDataValidatorInterface $hookSubscribeValidator
     * @param HookDataValidatorInterface $hookUnsubscribeValidator
     * @param WebhookDataToHookConverter $webhookConverter
     * @param HookRepositoryInterface $hookRepository
     * @param HookIdGenerator $hookIdGenerator
     * @param BatchOrderUpdaterInterface $batchOrderUpdater
     * @param LoggerInterface $logger
     * @param HookList $hookList
     */
    public function __construct(
        private readonly HookDataValidatorInterface $hookSubscribeValidator,
        private readonly HookDataValidatorInterface $hookUnsubscribeValidator,
        private readonly WebhookDataToHookConverter $webhookConverter,
        private readonly HookRepositoryInterface $hookRepository,
        private readonly HookIdGenerator $hookIdGenerator,
        private readonly BatchOrderUpdaterInterface $batchOrderUpdater,
        private readonly LoggerInterface $logger,
        private readonly HookList $hookList
    ) {
    }

    /**
     * @inheritDoc
     */
    public function subscribe(WebhookDataInterface $webhook): void
    {
        $requestHook = $this->webhookConverter->convert($webhook);
        $this->hookSubscribeValidator->validate($requestHook);

        $hookId = $this->hookIdGenerator->generateForHook($requestHook);
        $hook = $this->hookRepository->loadHook($hookId);

        if ($hook->getId()) {
            throw new ValidatorException(__('The webhook already exists in the app/etc/env.php configuration file.'));
        }

        $hook->setData($requestHook->getData());

        try {
            $hook = $this->hookRepository->save($hook);
            $this->batchOrderUpdater->execute($hook);
        } catch (LocalizedException $e) {
            throw new ValidatorException(__('Can not save the webhook. ' . $e->getMessage()));
        }

        $this->logger->info(
            sprintf(
                'The following webhook was registered via API: ' .
                'webhook_method=%s, webhook_type=%s, batch_name=%s, hook_name=%s',
                $webhook->getWebhookMethod(),
                $webhook->getWebhookType(),
                $webhook->getBatchName(),
                $webhook->getHookName()
            ),
            [
                'destination' => ['internal', 'external'],
                'hook' => $this->hookList->getById($hookId)
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function unsubscribe(WebhookDataInterface $webhook): void
    {
        try {
            $requestHook = $this->webhookConverter->convert($webhook);
            $this->hookUnsubscribeValidator->validate($requestHook);

            $hookId = $this->hookIdGenerator->generateForHook($requestHook);
            $hook = $this->hookRepository->loadHook($hookId);

            if (!$hook->getId()) {
                throw new ValidatorException(
                    __('The webhook does not exist in the app/etc/env.php configuration file.')
                );
            }

            $this->hookRepository->delete($hook);
        } catch (LocalizedException $e) {
            throw new ValidatorException(__('The webhook could not be deleted. ' . $e->getMessage()));
        }

        $this->logger->info(
            sprintf(
                'The following webhook was unregistered via API: ' .
                'webhook_method=%s, webhook_type=%s, batch_name=%s, hook_name=%s',
                $webhook->getWebhookMethod(),
                $webhook->getWebhookType(),
                $webhook->getBatchName(),
                $webhook->getHookName()
            ),
            [
                'destination' => ['internal', 'external'],
                'hook' => $this->hookList->getById($hookId)
            ]
        );
    }
}
