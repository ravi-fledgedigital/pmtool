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

namespace Magento\AdminUiSdkCustomFees\Model\ResourceModel\SalesOrder;

use Magento\AdminUiSdkCustomFees\Model\ResourceModel\CustomFees;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Custom fees collection
 */
class Collection extends AbstractCollection
{
    /**
     * Define resource model
     */
    public function _construct()
    {
        $this->_init(\Magento\AdminUiSdkCustomFees\Model\CustomFees::class, CustomFees::class);
    }
}
