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

use Magento\CommerceBackendUix\Model\Sanitizer\GridColumnSanitizer;

/**
 * Grid column parser for registration
 */
class GridColumnParser implements ParserInterface
{
    /**
     * @param GridColumnSanitizer $sanitizer
     * @param array $gridTypes
     */
    public function __construct(private GridColumnSanitizer $sanitizer, private array $gridTypes)
    {
    }

    /**
     * @inheritdoc
     */
    public function parse(array $loadedRegistrations, array &$parsedRegistrations, string $extensionId): void
    {
        foreach ($this->gridTypes as $gridType) {
            if (!isset($loadedRegistrations[$gridType]['gridColumns'])) {
                continue;
            }
            $sanitizedColumns = $this->sanitizer->sanitize($loadedRegistrations[$gridType]['gridColumns']);
            $parsedRegistrations[$gridType]['gridColumns'] = $sanitizedColumns;
        }
    }
}
