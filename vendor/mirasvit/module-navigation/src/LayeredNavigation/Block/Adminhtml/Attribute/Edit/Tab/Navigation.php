<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LayeredNavigation\Block\Adminhtml\Attribute\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Mirasvit\LayeredNavigation\Api\Data\AttributeConfigInterface;
use Mirasvit\LayeredNavigation\Model\ConfigProvider;
use Mirasvit\LayeredNavigation\Repository\AttributeConfigRepository;

class Navigation extends Generic implements TabInterface
{
    private $attributeConfigRepository;

    private $formFactory;

    private $registry;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     */
    private $attribute;

    public function __construct(
        AttributeConfigRepository $attributeConfigRepository,
        Context $context,
        Registry $registry,
        FormFactory $formFactory
    ) {
        $this->attributeConfigRepository = $attributeConfigRepository;
        $this->formFactory               = $formFactory;
        $this->registry                  = $registry;

        $this->attribute = $registry->registry('entity_attribute');

        parent::__construct($context, $registry, $formFactory);
    }


    public function getTabLabel(): string
    {
        return (string)__('Layered Navigation');
    }

    public function getTabTitle(): string
    {
        return $this->getTabLabel();
    }

    public function canShowTab(): bool
    {
        return true;
    }

    public function isHidden(): bool
    {
        return false;
    }

    protected function _prepareForm(): self
    {
        $form = $this->formFactory->create()->setData([
            'id'                => 'edit_form',
            'action'            => $this->getData('action'),
            'method'            => 'post',
            'enctype'           => 'multipart/form-data',
            'field_name_suffix' => 'attribute_config',
        ]);

        $attributeConfig = $this->getAttributeConfig();

        if (!$attributeConfig) {
            $form->addFieldset('base_fieldset', [
                'legend' => __('Layered Navigation configuration will be available after attribute creation'),
                'class'  => 'fieldset-wide',
            ]);

            $this->setForm($form);

            return parent::_prepareForm();
        }

        if (in_array($this->attribute->getAttributeCode(), ConfigProvider::ATTRIBUTES_EXCEPTIONS)) {
            $form->addFieldset('base_fieldset', [
                'legend' => __('Layered Navigation configuration is not available for this attribute'),
                'class'  => 'fieldset-wide',
            ]);

            $this->setForm($form);

            return parent::_prepareForm();
        }

        $frontendInput = $this->attribute->getFrontendInput();

        $form->addField(AttributeConfigInterface::ATTRIBUTE_CODE, 'hidden', [
            'name'  => AttributeConfigInterface::ATTRIBUTE_CODE,
            'value' => $attributeConfig->getAttributeCode(),
        ]);

        $form->addField('display', Fieldset\DisplayFieldset::class, [
            AttributeConfigInterface::class => $attributeConfig,
            Attribute::class                => $this->attribute,
        ]);
        
        $form->addField('visibility', Fieldset\VisibilityFieldset::class, [
            AttributeConfigInterface::class => $attributeConfig,
            Attribute::class                => $this->attribute,
        ]);

        $form->addField('misc', Fieldset\MiscFieldset::class, [
            AttributeConfigInterface::class => $attributeConfig,
            Attribute::class                => $this->attribute,
        ]);

        $attributeEditorPage = $this->registry->registry('mst_attribute_editor_page') === true;

        if (in_array($frontendInput, ['select', 'multiselect']) && !$attributeEditorPage) {
            $options = $this->getLayout()->createBlock(Element\OptionsConfig::class);

            $this->setChild('form_after', $options);
        }

        $this->setForm($form);

        if ($attributeEditorPage) {
            $form->setUseContainer(true);
        }

        return parent::_prepareForm();
    }

    protected function getAttributeConfig(): ?AttributeConfigInterface
    {
        if (!$this->attribute || !$this->attribute->getId()) {
            return null;
        }

        $settings = $this->attributeConfigRepository->getByAttributeCode((string)$this->attribute->getAttributeCode(), false);

        if (!$settings) {
            $settings = $this->attributeConfigRepository->create();
            $settings->setAttributeCode((string)$this->attribute->getAttributeCode())
                ->setAttributeId((int)$this->attribute->getId());
        }

        return $settings;
    }
}
