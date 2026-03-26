<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooks\Model\DataConverter;

/**
 * Uses for converting the list of mixed input arguments to array structure
 */
interface ArgumentDataConverterInterface
{
    public const MAX_DEPTH = 5;

    /**
     * Converts the list of mixed input arguments to array structure
     *
     * @param array $arguments
     * @param int $depth
     * @return array
     */
    public function convert(array $arguments, int $depth = 1): array;
}
