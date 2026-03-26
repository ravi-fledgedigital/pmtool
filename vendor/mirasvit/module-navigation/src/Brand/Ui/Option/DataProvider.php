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


namespace Mirasvit\Brand\Ui\Option;


use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Block\Adminhtml\Attribute\Edit\Options\Options;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\FieldFactory;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Form\FieldsetFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    private $fieldFactory;

    private $fieldsetFactory;

    private $optionsBlock;

    public function __construct(
        FieldFactory $fieldFactory,
        FieldsetFactory $fieldsetFactory,
        Options $optionsBlock,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        $this->fieldFactory    = $fieldFactory;
        $this->fieldsetFactory = $fieldsetFactory;
        $this->optionsBlock    = $optionsBlock;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        return;
    }

    public function getData()
    {
        return [];
    }

    public function getMeta()
    {
        $meta = parent::getMeta();

        $wrapperFieldset = $this->fieldsetFactory->create();
        $wrapperFieldset->setData([
            'name'   => 'new_brand_wrapper',
            'config' => [
                'componentType' => 'fieldset',
                'label'         => ' '
            ]
        ]);

        $optionFieldset = $this->fieldsetFactory->create();
        $optionFieldset->setData([
            'name'   => 'new_brand',
            'config' => [
                'componentType' => 'fieldset',
                'label'         => 'Brand labels'
            ]
        ]);

        foreach ($this->optionsBlock->getStoresSortedBySortOrder() as $store) {
            $field = $this->fieldFactory->create();

            $config = [
                'componentType' => 'field',
                'label'         => $store->getName(),
                'formElement'   => 'input',
                'dataType'      => 'text'
            ];

            if ($store->getId() == 0) {
                $config['validation'] = ['required-entry' => true];
            }

            $field->setData([
                'name'   => 'option[' . $store->getId() . ']',
                'config' => $config
            ]);

            $optionFieldset->addComponent('option[' . $store->getId() . ']', $field);
        }

        $wrapperFieldset->addComponent('new_brand', $optionFieldset);

        return $this->prepareComponent($wrapperFieldset)['children'];
    }

    protected function prepareComponent(UiComponentInterface $component): array
    {
        $data = [];
        foreach ($component->getChildComponents() as $name => $child) {
            $data['children'][$name] = $this->prepareComponent($child);
        }

        $data['arguments']['data']  = $component->getData();
        $data['arguments']['block'] = $component->getBlock();

        return $data;
    }
}
