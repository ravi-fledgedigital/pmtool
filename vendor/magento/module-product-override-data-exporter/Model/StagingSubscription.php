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

namespace Magento\ProductOverrideDataExporter\Model;

use Magento\CatalogStaging\Model\Mview\View\Attribute\Subscription;
use Magento\Framework\Mview\ViewInterface;

/**
 * Class Subscription implements statement building for staged entity attribute subscription
 */
class StagingSubscription extends Subscription
{
    /**
     * Build trigger statement for INSERT, UPDATE, DELETE events
     *
     * @param string $event
     * @param ViewInterface $view
     * @return string
     */
    protected function buildStatement(string $event, ViewInterface $view): string
    {
        $result = parent::buildStatement($event, $view);

        $linkId = $this->getColumnName();//entity_id
        $result = preg_replace('/(NEW|OLD)\.`row_id`/', "$1.`$linkId`", $result);
        return $result;
    }
}
