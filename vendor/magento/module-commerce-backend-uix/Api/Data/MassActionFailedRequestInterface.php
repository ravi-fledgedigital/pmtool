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

namespace Magento\CommerceBackendUix\Api\Data;

/**
 * Defines the mass actions failed requests database model
 *
 * @api
 */
interface MassActionFailedRequestInterface
{
    public const FIELD_ID = 'id';
    public const FIELD_REQUEST_ID = 'request_id';
    public const FIELD_ACTION_ID = 'action_id';
    public const FIELD_GRID_TYPE = 'grid_type';
    public const FIELD_ERROR_STATUS = 'error_status';
    public const FIELD_ERROR_MESSAGE = 'error_message';
    public const FIELD_REQUEST_TIMESTAMP = 'request_timestamp';
    public const FIELD_SELECTED_IDS = 'selected_ids';

    /**
     * Returns internal id
     *
     * @return string|null
     */
    public function getId(): ?string;

    /**
     * Returns request id
     *
     * @return string
     */
    public function getRequestId(): string;

    /**
     * Sets request id
     *
     * @param string $requestId
     * @return MassActionFailedRequestInterface
     */
    public function setRequestId(string $requestId): MassActionFailedRequestInterface;

    /**
     * Returns action id
     *
     * @return string
     */
    public function getActionId(): string;

    /**
     * Sets action id
     *
     * @param string $actionId
     * @return MassActionFailedRequestInterface
     */
    public function setActionId(string $actionId): MassActionFailedRequestInterface;

    /**
     * Returns ui grid type
     *
     * @return string
     */
    public function getGridType(): string;

    /**
     * Sets ui grid type
     *
     * @param string $gridType
     * @return MassActionFailedRequestInterface
     */
    public function setGridType(string $gridType): MassActionFailedRequestInterface;

    /**
     * Returns error status
     *
     * @return string
     */
    public function getErrorStatus(): string;

    /**
     * Sets error status
     *
     * @param string $errorStatus
     * @return MassActionFailedRequestInterface
     */
    public function setErrorStatus(string $errorStatus): MassActionFailedRequestInterface;

    /**
     * Returns error message
     *
     * @return string
     */
    public function getErrorMessage(): string;

    /**
     * Sets error message
     *
     * @param string $errorMessage
     * @return MassActionFailedRequestInterface
     */
    public function setErrorMessage(string $errorMessage): MassActionFailedRequestInterface;

    /**
     * Returns request timestamp
     *
     * @return string
     */
    public function getRequestTimestamp(): string;

    /**
     * Sets request timestamp
     *
     * @param string $requestTimestamp
     * @return MassActionFailedRequestInterface
     */
    public function setRequestTimestamp(string $requestTimestamp): MassActionFailedRequestInterface;

    /**
     * Returns selected ids
     *
     * @return string
     */
    public function getSelectedIds(): string;

    /**
     * Sets selected ids
     *
     * @param string $selectedIds
     * @return MassActionFailedRequestInterface
     */
    public function setSelectedIds(string $selectedIds): MassActionFailedRequestInterface;
}
