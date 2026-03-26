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

namespace Magento\AdobeCommerceWebhooks\Model\Filter;

use Magento\AdobeCommerceWebhooks\Model\Webhook\HookField;

/**
 * Converts HookField objects to aggregated Field objects
 */
class FieldConverter
{
    /**
     * Converts a list of HookFields to a list of Field objects.
     *
     * Ignore field expression if it is not a string.
     *
     * @param HookField[] $hookFields
     * @return Field[]
     */
    public function convert(array $hookFields): array
    {
        $fields = $registry = [];
        foreach ($hookFields as $hookField) {
            $name = $hookField->getSource() ?: $hookField->getName();
            if (empty($name)) {
                continue;
            }

            $fields[] = $this->buildField(
                explode('.', $name),
                $hookField,
                null,
                $registry
            );
        }
        return array_filter($fields);
    }

    /**
     * Builds a Field object based on the parts of a hook field expression.
     *
     * @param array $fieldParts
     * @param HookField $hookField
     * @param Field|null $parent
     * @param array $registry
     * @return Field|null
     */
    private function buildField(
        array $fieldParts,
        HookField $hookField,
        ?Field $parent = null,
        array &$registry = []
    ): ?Field {
        if (str_contains($fieldParts[0], '[]')) {
            $field = new Field(
                str_replace('[]', '', $fieldParts[0]),
                $parent,
                $hookField,
                true,
            );
        } else {
            $field = new Field(
                $fieldParts[0],
                $parent,
                $hookField,
                false
            );
        }

        if (isset($registry[$field->getPath()])) {
            $field = $registry[$field->getPath()];
            if (count($fieldParts) > 1) {
                $child = $this->buildField(array_slice($fieldParts, 1), $hookField, $field, $registry);
                if ($child) {
                    $field->addChildren($child);
                }
            }
            return null;
        }

        $registry[$field->getPath()] = $field;

        if (count($fieldParts) > 1) {
            $field->addChildren($this->buildField(array_slice($fieldParts, 1), $hookField, $field, $registry));
        }
        return $field;
    }
}
