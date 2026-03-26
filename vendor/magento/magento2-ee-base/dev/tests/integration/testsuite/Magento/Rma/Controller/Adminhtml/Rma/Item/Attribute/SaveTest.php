<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Controller\Adminhtml\Rma\Item\Attribute;

use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\Framework\Data\Form\FormKey;

/**
 * @magentoAppArea adminhtml
 */
class SaveTest extends AbstractBackendController
{
    /**
     * Tests that controller validate file extensions.
     *
     * @return void
     */
    public function testFileExtensions(): void
    {
        $params = $this->getRequestNewAttributeData();
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPostValue($params);

        $this->dispatch('backend/admin/rma_item_attribute/save');

        $this->assertSessionMessages(
            $this->equalTo(['Please correct the value for file extensions.'])
        );
    }

    /**
     * Tests that default form is assigned to attribute if no form is assigned.
     *
     * @return void
     */
    public function testShouldAssignDefaultFormToAttributeIfNoFormIsAssigned(): void
    {
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPostValue(
            [
                'attribute_code' => 'rma_item_attribute',
                'frontend_label' => ['rma_item_attribute'],
                'frontend_input' => 'text',
                'used_in_forms' => '',
                'sort_order' => 150,
                'form_key' => $this->_objectManager->get(FormKey::class)->getFormKey(),
            ]
        );

        $this->dispatch('backend/admin/rma_item_attribute/save');

        $this->assertSessionMessages(
            $this->equalTo(['You saved the RMA item attribute.']),
            \Magento\Framework\Message\MessageInterface::TYPE_SUCCESS
        );

        /** @var $attribute \Magento\Rma\Model\Item\Attribute */
        $attribute = $this->_objectManager->create(\Magento\Rma\Model\Item\Attribute::class);
        $attribute->loadByCode(\Magento\Rma\Model\Item::ENTITY, 'rma_item_attribute');
        $this->assertEquals(['default'], $attribute->getUsedInForms());
    }

    /**
     * Gets request params.
     *
     * @return array
     */
    private function getRequestNewAttributeData(): array
    {
        return [
            'attribute_code' => 'new_file',
            'frontend_label' => ['new_file'],
            'frontend_input' => 'file',
            'file_extensions' => 'php',
            'sort_order' => 1,
            'form_key' => $this->_objectManager->get(FormKey::class)->getFormKey(),
        ];
    }
}
