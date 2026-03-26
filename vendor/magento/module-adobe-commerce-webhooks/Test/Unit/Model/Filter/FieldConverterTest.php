<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2025 Adobe
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

namespace Magento\AdobeCommerceWebhooks\Test\Unit\Model\Filter;

use Magento\AdobeCommerceWebhooks\Model\Filter\FieldConverter;
use Magento\AdobeCommerceWebhooks\Model\Webhook\HookField;
use PHPUnit\Framework\TestCase;

/**
 * Test for @see FieldConverter
 */
class FieldConverterTest extends TestCase
{
    /**
     * @var FieldConverter
     */
    private FieldConverter $fieldConverter;

    protected function setUp(): void
    {
        $this->fieldConverter = new FieldConverter();
    }

    public function testConvertMultipleHookFields()
    {
        $hookFields = [
            new HookField(['name' => 'field1']),
            new HookField(['name' => 'field2', 'source' => '']),
            new HookField(['name' => 'field3', 'source' => 'source3']),
            new HookField(['name' => 'field4.subsource', 'source' => 'source4.subsource']),
        ];

        $fields = $this->fieldConverter->convert($hookFields);

        self::assertCount(4, $fields);
        self::assertEquals('field1', $fields[0]->getName());
        self::assertEquals('field1', $fields[0]->getPath());
        self::assertEquals('field2', $fields[1]->getName());
        self::assertEquals('field2', $fields[1]->getPath());
        self::assertEquals('source3', $fields[2]->getName());
        self::assertEquals('source3', $fields[2]->getPath());
        self::assertCount(1, $fields[3]->getChildren());
        self::assertEquals('subsource', $fields[3]->getChildren()[0]->getName());
    }

    public function testConvertMultipleHookFieldsWithTheSameParent()
    {
        $hookFields = [
            new HookField(['name' => 'field.subfield1', 'source' => 'field.subfield1']),
            new HookField(['name' => 'field.subfield2', 'source' => '']),
            new HookField(['name' => 'field.subfield3', 'source' => '']),
            new HookField(['name' => 'field.subfield4', 'source' => 'field.subfield4']),
            new HookField(['name' => 'field2.subfield5.subfield6', 'source' => '']),
        ];

        $fields = array_values($this->fieldConverter->convert($hookFields));

        self::assertCount(2, $fields);
        self::assertCount(4, $fields[0]->getChildren());
        $childFields = $fields[0]->getChildren();
        self::assertEquals('subfield1', $childFields[0]->getName());
        self::assertEquals('field.subfield1', $childFields[0]->getPath());
        self::assertEquals('subfield2', $childFields[1]->getName());
        self::assertEquals('field.subfield2', $childFields[1]->getPath());
        self::assertCount(1, $fields[1]->getChildren());
        self::assertCount(1, $fields[1]->getChildren()[0]->getChildren());
    }
}
