<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Api\Data;

/**
 * Defines the mass actions database model
 *
 * @deprecated Not used anymore, no need to store data in database
 * @api
 */
interface MassActionInterface
{
    public const FIELD_ID = 'id';
    public const FIELD_LABEL = 'label';
    public const FIELD_TYPE = 'type';
    public const FIELD_UI_GRID = 'ui_grid';
    public const FIELD_CONFIRM_TITLE = 'confirm_title';
    public const FIELD_CONFIRM_MESSAGE = 'confirm_message';
    public const FIELD_ACTION_ID = 'action_id';
    public const FIELD_EXTENSION_ID = 'extension_id';
    public const FIELD_PRODUCT_NUMBER_LIMIT = 'product_number_limit';

    /**
     * Returns event id.
     *
     * @return null|string
     */
    public function getId(): ?string;

    /**
     * Returns label.
     *
     * @return null|string
     */
    public function getLabel(): ?string;

    /**
     * Sets label.
     *
     * @param string $label
     * @return MassActionInterface
     */
    public function setLabel(string $label): MassActionInterface;

    /**
     * Returns type.
     *
     * @return null|string
     */
    public function getType(): ?string;

    /**
     * Sets type.
     *
     * @param string $type
     * @return MassActionInterface
     */
    public function setType(string $type): MassActionInterface;

    /**
     * Returns ui grid.
     *
     * @return null|string
     */
    public function getUiGrid(): ?string;

    /**
     * Sets ui grid.
     *
     * @param string $uiGrid
     * @return MassActionInterface
     */
    public function setUiGrid(string $uiGrid): MassActionInterface;

    /**
     * Returns confirm title.
     *
     * @return null|string
     */
    public function getConfirmTitle(): ?string;

    /**
     * Sets confirm title.
     *
     * @param string $confirmTitle
     * @return MassActionInterface
     */
    public function setConfirmTitle(string $confirmTitle): MassActionInterface;

    /**
     * Returns confirm message.
     *
     * @return null|string
     */
    public function getConfirmMessage(): ?string;

    /**
     * Sets confirm message.
     *
     * @param string $confirmMessage
     * @return MassActionInterface
     */
    public function setConfirmMessage(string $confirmMessage): MassActionInterface;

    /**
     * Returns action id.
     *
     * @return null|string
     */
    public function getActionId(): ?string;

    /**
     * Sets action id.
     *
     * @param string $actionId
     * @return MassActionInterface
     */
    public function setActionId(string $actionId): MassActionInterface;

    /**
     * Returns extension id.
     *
     * @return null|string
     */
    public function getExtensionId(): ?string;

    /**
     * Sets extension id.
     *
     * @param string $extensionId
     * @return MassActionInterface
     */
    public function setExtensionId(string $extensionId): MassActionInterface;

    /**
     * Returns product number limit.
     *
     * @return null|int
     */
    public function getProductNumberLimit(): ?int;

    /**
     * Sets product number limit.
     *
     * @param int $productNumberLimit
     * @return MassActionInterface
     */
    public function setProductNumberLimit(int $productNumberLimit): MassActionInterface;

}
