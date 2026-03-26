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

use Magento\Backend\Block\Widget\Form\Element\Dependence;
use Magento\Config\Model\Config\Structure\Element\Dependency\FieldFactory as DependencyFieldFactory;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\Escaper;
use Magento\Framework\View\LayoutInterface;
use Mirasvit\LayeredNavigation\Api\Data\AttributeConfigInterface;
use Mirasvit\LayeredNavigation\Model\Config\Source\VisibleInCategorySource;
use Magento\Backend\Model\UrlInterface;

/**
 * @SuppressWarnings(PHPMD)
 */
class VisibilityFieldset extends Fieldset
{
    private $visibleInCategorySource;

    private $layout;

    /**
     * @var Attribute
     */
    private $attribute;

    private $dependencyFieldFactory;

    /**
     * @var AttributeConfigInterface
     */
    private $attributeConfig;

    private UrlInterface $urlBuilder;

    public function __construct(
        VisibleInCategorySource $visibleInCategorySource,
        LayoutInterface $layout,
        DependencyFieldFactory $dependencyFieldFactory,
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        UrlInterface $urlBuilder,
        array $data = []
    ) {
        $this->visibleInCategorySource    = $visibleInCategorySource;
        $this->layout                     = $layout;
        $this->dependencyFieldFactory     = $dependencyFieldFactory;

        $this->attributeConfig = $data[AttributeConfigInterface::class];
        $this->attribute       = $data[Attribute::class];
        $this->urlBuilder      = $urlBuilder;

        parent::__construct($factoryElement, $factoryCollection, $escaper, [
            'legend' => __('Visibility'),
        ]);
    }

    /**
     * @return string
     */
    public function getBasicChildrenHtml()
    {
        $visibilityField = $this->addField(
            AttributeConfigInterface::CATEGORY_VISIBILITY_MODE,
            'select',
            [
                'name'   => AttributeConfigInterface::CATEGORY_VISIBILITY_MODE,
                'label'  => __('Categories Visibility Mode'),
                'values' => $this->visibleInCategorySource->toOptionArray(),
                'value'  => $this->attributeConfig->getCategoryVisibilityMode(),
            ]
        );

        $categoryField = $this->addField(
            AttributeConfigInterface::CATEGORY_VISIBILITY_IDS,
            'note',
            [
                'label' => __('Categories'),
                'text'  => $this->getCategoryTreeHtml()
            ]
        );

        /** @var Dependence $dependence */
        $dependence = $this->layout->createBlock(Dependence::class);
        if ($this->attribute->getAttributeCode() == 'category_ids') {
            $searchVisibilityField = $this->addField(
                AttributeConfigInterface::SEARCH_VISIBILITY_MODE,
                'select',
                [
                    'name'   => AttributeConfigInterface::SEARCH_VISIBILITY_MODE,
                    'label'  => __('Hide Filter in Search results'),
                    'values' => [0 => __('No'), 1 => __('Yes')],
                    'value'  => $this->attributeConfig->getSearchVisibilityMode(),
                ]
            );

            $dependence->addFieldMap(
                $searchVisibilityField->getHtmlId(),
                $searchVisibilityField->getName()
            );
        }

        $dependence->addFieldMap(
            $visibilityField->getHtmlId(),
            $visibilityField->getName()
        )->addFieldMap(
            $categoryField->getHtmlId(),
            $categoryField->getName()
        )->addFieldDependence(
            $categoryField->getName(),
            $visibilityField->getName(),
            $this->dependencyFieldFactory->create([
                'fieldData'   => [
                    'value'    => AttributeConfigInterface::CATEGORY_VISIBILITY_MODE_ALL,
                    'negative' => true,
                ],
                'fieldPrefix' => '',
            ])
        );

        return parent::getBasicChildrenHtml() . $dependence->toHtml();
    }

    private function getCategoryTreeHtml(): string
    {
        $values = $this->attributeConfig->getCategoryVisibilityIds();
        $values = is_array($values) ? $values : [];

        $categoryTreeHtml = '
        <div id="category_tree_container" data-selected-categories=[' . implode(',', $values) . ']></div>
        <input type="hidden" name="attribute_config[' . AttributeConfigInterface::CATEGORY_VISIBILITY_IDS . ']" 
               value="' . implode(',', $values) . '" class="category-tree-field"/>

        <script type="text/x-magento-init">
        {
            "#category_tree_container": {
                "Mirasvit_LayeredNavigation/js/category-tree": {
                    "url": "' . $this->urlBuilder->getUrl('layered_navigation/category/tree') . '",
                    "selectedCategories": ' . json_encode($values) . '
                }
            }
        }
        </script>';

        return $categoryTreeHtml;
    }
}
