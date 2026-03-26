<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * -------------------
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
 ***********************************************************************/
declare(strict_types=1);

namespace Magento\Rma\Setup\Patch\Data;

use Magento\Framework\Exception\LocalizedException;
use Magento\Rma\Model\Item;
use Magento\Rma\Model\Item\Attribute;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Test for AssignRmaAttributesWithNoAssignedFormsToDefaultForm data patch.
 */
class AssignRmaAttributesWithNoAssignedFormsToDefaultFormTest extends TestCase
{
    /**
     * @var AssignRmaAttributesWithNoAssignedFormsToDefaultForm
     */
    private $model;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->model = ObjectManager::getInstance()->get(
            AssignRmaAttributesWithNoAssignedFormsToDefaultForm::class
        );
    }

    /**
     * Test that non-system RMA attributes that are not assigned to any form will be assigned to default form.
     *
     * @magentoDataFixture Magento/Rma/_files/rma_item_attribute.php
     * @return void
     */
    public function testShouldAssignDefaultFormToAttributeIfNoFormIsAssigned(): void
    {
        // Check that non system attribute is not assigned to any form
        $this->assertEquals([], $this->getAttribute('rma_item_attribute')->getUsedInForms());

        // Check that system attribute is not assigned to any form
        $this->assertEquals([], $this->getAttribute('qty_authorized')->getUsedInForms());

        // Apply patch
        $this->model->apply();

        // Check that non system attribute is now assigned to default form
        $this->assertEquals(['default'], $this->getAttribute('rma_item_attribute')->getUsedInForms());

        // Check that system attribute is still not assigned to any form
        $this->assertEquals([], $this->getAttribute('qty_authorized')->getUsedInForms());
    }

    /**
     * @param string $attributeCode
     * @return Attribute
     * @throws LocalizedException
     */
    private function getAttribute(string $attributeCode): Attribute
    {
        /** @var $attribute Attribute */
        $attribute = ObjectManager::getInstance()->create(Attribute::class);
        $attribute->loadByCode(Item::ENTITY, $attributeCode);
        return $attribute;
    }
}
