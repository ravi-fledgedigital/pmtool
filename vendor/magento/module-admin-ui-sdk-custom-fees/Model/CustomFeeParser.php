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

use Magento\CommerceBackendUix\Model\Sanitizer\InputSanitizer;
use Magento\CommerceBackendUix\Model\Parser\ParserInterface;

/**
 * Custom fee parser for registrations
 */
class CustomFeeParser implements ParserInterface
{
    private const CUSTOM_FEES = 'customFees';
    private const ORDER = 'order';

    /**
     * @param InputSanitizer $sanitizer
     */
    public function __construct(private InputSanitizer $sanitizer)
    {
    }

    /**
     * @inheritDoc
     */
    public function parse(array $loadedRegistrations, array &$parsedRegistrations, string $extensionId): void
    {
        if (!isset($loadedRegistrations[self::ORDER][self::CUSTOM_FEES])) {
            return;
        }
        $sanitizedCustomFees = $this->sanitizer->sanitize($loadedRegistrations[self::ORDER][self::CUSTOM_FEES]);
        if (!empty($sanitizedCustomFees)) {
            $parsedRegistrations[self::ORDER][self::CUSTOM_FEES] = $sanitizedCustomFees;
        }
    }
}
