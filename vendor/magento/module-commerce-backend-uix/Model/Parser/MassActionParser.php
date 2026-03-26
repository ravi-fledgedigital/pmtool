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
 * Mass action parser for registration
 */
class MassActionParser implements ParserInterface
{
    /**
     * @param InputSanitizer $inputSanitizer
     * @param array $gridTypes
     */
    public function __construct(private InputSanitizer $inputSanitizer, private array $gridTypes)
    {
    }

    /**
     * @inheritdoc
     */
    public function parse(array $loadedRegistrations, array &$parsedRegistrations, string $extensionId): void
    {
        foreach ($this->gridTypes as $gridType) {
            if (!isset($loadedRegistrations[$gridType]['massActions'])) {
                continue;
            }
            $this->processMassActions(
                $loadedRegistrations[$gridType]['massActions'],
                $extensionId,
                $parsedRegistrations,
                $gridType
            );
        }
    }

    /**
     * Process and complete mass actions
     *
     * @param array $massActions
     * @param string $extensionId
     * @param array $parsedRegistrations
     * @param string $gridType
     * @return void
     */
    private function processMassActions(
        array $massActions,
        string $extensionId,
        array &$parsedRegistrations,
        string $gridType
    ): void {
        $sanitizedMassActions = $this->inputSanitizer->sanitize($massActions);
        foreach ($sanitizedMassActions as $massAction) {
            $massAction['extensionId'] = $extensionId;
            $massAction['selectionLimit'] = $massAction['selectionLimit'] ?? -1;
            $parsedRegistrations[$gridType]['massActions'][] = $massAction;
        }
    }
}
