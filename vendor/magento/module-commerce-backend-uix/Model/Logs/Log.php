<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2024 Adobe
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

namespace Magento\CommerceBackendUix\Model\Logs;

use Magento\CommerceBackendUix\Api\Data\LogInterface;
use Magento\CommerceBackendUix\Model\ResourceModel\Logs as ResourceModel;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Class Log model
 */
class Log extends AbstractModel implements LogInterface, IdentityInterface
{
    /**
     * @inheritDoc
     */
    public function _construct(): void
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * @inheritdoc
     */
    public function getIdentities(): array
    {
        return [$this->getId()];
    }

    /**
     * @inheritdoc
     */
    public function getId(): ?string
    {
        return parent::getData(self::FIELD_ID);
    }

    /**
     * @inheritdoc
     */
    public function getMessage(): string
    {
        return parent::getData(self::FIELD_MESSAGE);
    }

    /**
     * @inheritdoc
     */
    public function setMessage(string $message): LogInterface
    {
        $this->setData(self::FIELD_MESSAGE, $message);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getLevel(): string
    {
        return parent::getData(self::FIELD_LEVEL);
    }

    /**
     * @inheritdoc
     */
    public function setLevel(string $level): LogInterface
    {
        $this->setData(self::FIELD_LEVEL, $level);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTimestamp(): string
    {
        return parent::getData(self::FIELD_TIMESTAMP);
    }

    /**
     * @inheritdoc
     */
    public function setTimestamp(string $timestamp): LogInterface
    {
        $this->setData(self::FIELD_TIMESTAMP, $timestamp);
        return $this;
    }
}
