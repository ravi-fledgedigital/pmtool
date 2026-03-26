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

namespace Magento\CommerceBackendUix\Api;

use Magento\CommerceBackendUix\Api\Data\MassActionInterface;
use Magento\Framework\Exception\AlreadyExistsException;

/**
 * Mass Actions storage interface
 *
 * @deprecated Not used anymore, no need to store data in database
 * @api
 */
interface MassActionRepositoryInterface
{
    /**
     * Load mass actions by UI Grid
     *
     * @param String $uiGrid
     * @return array
     */
    public function getByUiGrid(String $uiGrid): array;

    /**
     * Load the mass action by action id.
     *
     * @param String $actionId
     * @return int
     */
    public function getProductNumberLimitByActionId(String $actionId): int;

    /**
     * Saving the mass action.
     *
     * @param MassActionInterface $massAction
     * @return MassActionInterface
     * @throws AlreadyExistsException
     */
    public function save(MassActionInterface $massAction): MassActionInterface;

    /**
     * Delete all mass actions by Ui Grid
     *
     * @param String $uiGrid
     * @return void
     */
    public function deleteAllByUiGrid(String $uiGrid): void;
}
