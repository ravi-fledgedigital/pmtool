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

namespace Magento\CommerceBackendUix\Plugin\Ui\Catalog\Product;

use Magento\CommerceBackendUix\Model\AuthorizationValidator;
use Magento\CommerceBackendUix\Model\UiGridType;
use Magento\CommerceBackendUix\Ui\Component\MassAction as ComponentMassAction;

/**
 * Plugin class to append product mass actions for Apps
 */
class MassAction
{
    /**
     * @param ComponentMassAction $componentMassAction
     * @param AuthorizationValidator $authorization
     */
    public function __construct(
        private ComponentMassAction $componentMassAction,
        private AuthorizationValidator $authorization
    ) {
    }

    /**
     * After prepare function to load registered mass actions
     *
     * @param \Magento\Catalog\Ui\Component\Product\MassAction $subject
     * @return void
     */
    public function afterPrepare(\Magento\Catalog\Ui\Component\Product\MassAction $subject): void
    {
        if (!$this->authorization->isAuthorized()) {
            return;
        }
        $config = $subject->getConfiguration();
        $config['actions'] = array_merge(
            $config['actions'],
            $this->componentMassAction->getMassActionsConfig(UiGridType::PRODUCT_LISTING_GRID)
        );
        $subject->setData('config', $config);
    }
}
