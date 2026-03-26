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

namespace Magento\CommerceBackendUix\Model;

use Magento\CommerceBackendUix\Api\Data\MassActionFailedRequestInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\CommerceBackendUix\Model\ResourceModel\MassActionFailedRequest as ResourceModel;

/**
 * @inheritDoc
 */
class MassActionFailedRequest extends AbstractModel implements MassActionFailedRequestInterface, IdentityInterface
{
    /**
     * @inheritDoc
     */
    public function _construct(): void
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * @inheritDoc
     */
    public function getIdentities(): array
    {
        return [$this->getId()];
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
    public function getRequestId(): string
    {
        return parent::getData(self::FIELD_REQUEST_ID);
    }

    /**
     * @inheritDoc
     */
    public function setRequestId(string $requestId): MassActionFailedRequestInterface
    {
        $this->setData(self::FIELD_REQUEST_ID, $requestId);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getActionId(): string
    {
        return parent::getData(self::FIELD_ACTION_ID);
    }

    /**
     * @inheritDoc
     */
    public function setActionId(string $actionId): MassActionFailedRequestInterface
    {
        $this->setData(self::FIELD_ACTION_ID, $actionId);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getGridType(): string
    {
        return parent::getData(self::FIELD_GRID_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setGridType(string $gridType): MassActionFailedRequestInterface
    {
        $this->setData(self::FIELD_GRID_TYPE, $gridType);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getErrorStatus(): string
    {
        return parent::getData(self::FIELD_ERROR_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setErrorStatus(string $errorStatus): MassActionFailedRequestInterface
    {
        $this->setData(self::FIELD_ERROR_STATUS, (int) $errorStatus);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getErrorMessage(): string
    {
        return parent::getData(self::FIELD_ERROR_MESSAGE);
    }

    /**
     * @inheritDoc
     */
    public function setErrorMessage(string $errorMessage): MassActionFailedRequestInterface
    {
        $this->setData(self::FIELD_ERROR_MESSAGE, $errorMessage);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRequestTimestamp(): string
    {
        return parent::getData(self::FIELD_REQUEST_TIMESTAMP);
    }

    /**
     * @inheritDoc
     */
    public function setRequestTimestamp(string $requestTimestamp): MassActionFailedRequestInterface
    {
        $this->setData(self::FIELD_REQUEST_TIMESTAMP, $requestTimestamp);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSelectedIds(): string
    {
        return parent::getData(self::FIELD_SELECTED_IDS);
    }

    /**
     * @inheritDoc
     */
    public function setSelectedIds(string $selectedIds): MassActionFailedRequestInterface
    {
        $this->setData(self::FIELD_SELECTED_IDS, $selectedIds);
        return $this;
    }
}
