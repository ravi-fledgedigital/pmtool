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

namespace Magento\AdminUiSdkCustomFees\Model;

use Magento\AdminUiSdkCustomFees\Api\Data\CustomFeesInterface;
use Magento\Framework\DataObject;

/**
 * Custom fee data object factory
 */
class CustomFeeDataObjectFactory
{
    private const CODE = 'code';
    private const LABEL = 'label';
    private const VALUE = 'value';
    private const BASE_VALUE = 'base_value';

    /**
     * Create a DataObject instance based on the custom fee data
     *
     * @param CustomFeesInterface $customFee
     * @return DataObject
     */
    public function create(CustomFeesInterface $customFee): DataObject
    {
        return new DataObject([
            self::CODE => $customFee[CustomFeesInterface::FIELD_FEE_CODE],
            self::VALUE => $customFee[CustomFeesInterface::FIELD_FEE_AMOUNT],
            self::BASE_VALUE => $customFee[CustomFeesInterface::FIELD_BASE_FEE_AMOUNT],
            self::LABEL => $customFee[CustomFeesInterface::FIELD_FEE_LABEL]
        ]);
    }
}
