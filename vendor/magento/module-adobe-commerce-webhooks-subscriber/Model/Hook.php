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

use Magento\AdobeCommerceWebhooks\Model\Config\WebhookConfigurationException;
use Magento\AdobeCommerceWebhooksSubscriber\Api\Data\HookInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * @inheritDoc
 */
class Hook extends DataObject implements HookInterface
{
    /**
     * @param Json $json
     * @param array $data
     */
    public function __construct(
        private readonly Json $json,
        array $data = []
    ) {
        parent::__construct($data);
    }

    /**
     * @inheritDoc
     */
    public function getId(): ?string
    {
        return parent::getData(self::FIELD_ID);
    }

    /**
     * @inheritDoc
     */
    public function getWebhookMethod(): string
    {
        return (string)parent::getData(self::FIELD_WEBHOOK_METHOD);
    }

    /**
     * @inheritDoc
     */
    public function getWebhookType(): string
    {
        return (string)parent::getData(self::FIELD_WEBHOOK_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function getBatchName(): string
    {
        return (string)parent::getData(self::FIELD_BATCH_NAME);
    }

    /**
     * @inheritDoc
     */
    public function getBatchOrder(): int
    {
        return (int)parent::getData(self::FIELD_BATCH_ORDER);
    }

    /**
     * @inheritDoc
     */
    public function setBatchOrder(int $batchOrder): HookInterface
    {
        return $this->setData(self::FIELD_BATCH_ORDER, $batchOrder);
    }

    /**
     * @inheritDoc
     */
    public function getHookName(): string
    {
        return (string)parent::getData(self::FIELD_HOOK_NAME);
    }

    /**
     * @inheritDoc
     */
    public function getHookData(): array
    {
        if (is_array($this->getData(self::FIELD_HOOK_DATA))) {
            return $this->getData(self::FIELD_HOOK_DATA);
        }

        $hookData = [];
        if ($data = $this->getData(self::FIELD_HOOK_DATA)) {
            try {
                $hookData = $this->json->unserialize($data);
            } catch (\InvalidArgumentException $exception) {
                throw new WebhookConfigurationException(
                    __('Cannot deserialize hook data for hook with id \'%1\'.', $this->getId()),
                    $exception
                );
            }
        }

        if (!is_array($hookData)) {
            throw new WebhookConfigurationException(
                __('Hook data for hook with id \'%1\' has an unexpected type. Expected an array.', $this->getId())
            );
        }

        return $hookData;
    }
}
