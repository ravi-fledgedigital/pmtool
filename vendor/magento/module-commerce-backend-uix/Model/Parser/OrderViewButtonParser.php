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

namespace Magento\CommerceBackendUix\Model\Parser;

use Magento\CommerceBackendUix\Model\Sanitizer\InputSanitizer;

/**
 * Order view button parser for registration
 */
class OrderViewButtonParser implements ParserInterface
{
    /**
     * @param InputSanitizer $inputSanitizer
     */
    public function __construct(private InputSanitizer $inputSanitizer)
    {
    }

    /**
     * @inheritdoc
     */
    public function parse(array $loadedRegistrations, array &$parsedRegistrations, string $extensionId): void
    {
        if (!isset($loadedRegistrations['order']['viewButtons'])) {
            return;
        }
        $sanitizedViewButtons = $this->inputSanitizer->sanitize($loadedRegistrations['order']['viewButtons']);
        foreach ($sanitizedViewButtons as $viewButton) {
            $viewButton['extensionId'] = $extensionId;
            $parsedRegistrations['order']['viewButtons'][] = $viewButton;
        }
    }
}
