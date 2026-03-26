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

namespace Mirasvit\LayeredNavigation\Block\Adminhtml\Attribute\Edit\Tab\Fieldset;

use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\Escaper;
use Mirasvit\LayeredNavigation\Api\Data\AttributeConfigInterface;
use Mirasvit\LayeredNavigation\Model\Config\Source\AttributeOptionSortBySource;

class MiscFieldset extends Fieldset
{
    private $attributeOptionSortBySource;


    /** @var Attribute */
    private $attribute;

    /** @var AttributeConfigInterface */
    private $attributeConfig;

    public function __construct(
        AttributeOptionSortBySource $attributeOptionSortBySource,
        Factory                     $factoryElement,
        CollectionFactory           $factoryCollection,
        Escaper                     $escaper,
        array                       $data = []
    ) {

        $this->attributeOptionSortBySource = $attributeOptionSortBySource;
        $this->attributeConfig             = $data[AttributeConfigInterface::class];
        $this->attribute                   = $data[Attribute::class];

        parent::__construct($factoryElement, $factoryCollection, $escaper, [
            'legend' => __('Additional'),
        ]);
    }

    public function getBasicChildrenHtml(): string
    {
        $this->addField(AttributeConfigInterface::ENABLE_MULTISELECT, 'select', [
            'name'   => AttributeConfigInterface::ENABLE_MULTISELECT,
            'label'  => __('Enable Multiselect'),
            'value'  => $this->attributeConfig->isMultiselectEnabled() ?? 2,
            'values' => [2 => __('Default'), 0 => __('No'), 1 => __('Yes')],
        ]);

        if (in_array($this->attribute->getFrontendInput(), ['select', 'multiselect'])) {
            $this->addField(AttributeConfigInterface::MULTISELECT_LOGIC, 'select', [
                'name'   => AttributeConfigInterface::MULTISELECT_LOGIC,
                'label'  => __('Multiselect Logic'),
                'values' => [0 => __('OR'), 1 => __('AND')],
                'value'  => $this->attributeConfig->getMultiselectLogic(),
            ]);
        }

        return (string)parent::getBasicChildrenHtml();
    }
}
