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
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\CatalogLabel\Model\Label\Rule\Condition;

use Magento\Backend\Model\Url;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\ProductFactory;
use Magento\CatalogRule\Model\Rule\Condition\Product as ProductRule;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\DataObject;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\Context;
use Mirasvit\CatalogLabel\Model\Label\Rule\Condition\Product\AbstractProductCondition;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Product extends AbstractCondition
{
    /**
     * @var string
     */
    protected $_isUsedForRuleProperty = 'is_used_for_promo_rules';

    protected $productFactory;

    protected $backendUrlManager;

    protected $localeFormat;

    protected $assetRepo;

    protected $context;

    protected $eavConfig;

    protected $dateTime;

    protected $_entityAttributeValues = null;

    /**
     * @var AbstractProductCondition[]
     */
    private $extraRulesPool;

    public function __construct(
        ProductFactory $productFactory,
        Url $backendUrlManager,
        FormatInterface $localeFormat,
        Config $eavConfig,
        Context $context,
        DateTime $dateTime,
        array $extraRulesPool,
        array $data = []
    ) {
        $this->productFactory    = $productFactory;
        $this->backendUrlManager = $backendUrlManager;
        $this->localeFormat      = $localeFormat;
        $this->context           = $context;
        $this->assetRepo         = $context->getAssetRepository();
        $this->extraRulesPool    = $extraRulesPool;
        $this->eavConfig         = $eavConfig;
        $this->dateTime          = $dateTime;

        parent::__construct($context, $data);
    }

    /**
     * @return AbstractAttribute|DataObject
     */
    public function getAttributeObject(): DataObject
    {
        try {
            $obj = $this->eavConfig
                ->getAttribute('catalog_product', $this->getAttribute());
        } catch (\Exception $e) {
            $obj = new \Magento\Framework\DataObject();
            $obj->setEntity($this->productFactory->create())
                ->setFrontendInput('text');
        }

        return $obj;
    }

    protected function _addSpecialAttributes(array &$attributes): void
    {
        foreach ($this->extraRulesPool as $rule) {
            $attributes[$rule->getCode()] = $rule->getLabel();
        }
    }

    public function loadAttributeOptions(): self
    {
        $productAttributes = $this->productFactory->create()
            ->loadAllAttributes();

        if ($productAttributes) {
            $productAttributes = $productAttributes->getAttributesByCode();
        } else {
            $productAttributes = [];
        }

        $attributes = [];

        foreach ($productAttributes as $attribute) {
            if (
                !$attribute->isAllowedForRuleCondition() ||
                !$attribute->getDataUsingMethod($this->_isUsedForRuleProperty)
            ) {
                continue;
            }

            $attributes[$attribute->getAttributeCode()] = $attribute->getFrontendLabel();
        }

        $this->_addSpecialAttributes($attributes);

        asort($attributes);
        $this->setAttributeOption($attributes);

        return $this;
    }

    protected function _prepareValueOptions(): self
    {
        // Check that both keys exist. Maybe somehow only one was set not in this routine, but externally.
        $selectReady = $this->getData('value_select_options');
        $hashedReady = $this->getData('value_option');

        if ($selectReady && $hashedReady) {
            return $this;
        }

        // Get array of select options. It will be used as source for hashed options
        $selectOptions = null;

        if (isset($this->extraRulesPool[$this->getAttribute()])) {
            $selectOptions = $this->extraRulesPool[$this->getAttribute()]->getValueOptions();
        } elseif (is_object($this->getAttributeObject())) {
            $attributeObject = $this->getAttributeObject();

            if ($attributeObject->usesSource()) {
                if ($attributeObject->getFrontendInput() == 'multiselect') {
                    $addEmptyOption = false;
                } else {
                    $addEmptyOption = true;
                }

                $selectOptions = $attributeObject->getSource()->getAllOptions($addEmptyOption);
            }
        }

        $this->setSelectOptions($selectOptions);

        return $this;
    }

    private function setSelectOptions(?array $selectOptions = null): void
    {
        $selectReady = $this->getData('value_select_options');
        $hashedReady = $this->getData('value_option');
        // Set new values only if we really got them
        if ($selectOptions !== null) {
            // Overwrite only not already existing values
            if (!$selectReady) {
                $this->setData('value_select_options', $selectOptions);
            }
            if (!$hashedReady) {
                $hashedOptions = [];

                foreach ($selectOptions as $o) {
                    if (is_array($o['value'])) {
                        continue; // We cannot use array as index
                    }

                    $hashedOptions[$o['value']] = $o['label'];
                }

                $this->setData('value_option', $hashedOptions);
            }
        }
    }

    public function getValueOption(?string $option = null): string
    {
        $this->_prepareValueOptions();

        return (string)$this->getData('value_option' . ($option !== null ? '/' . $option : ''));
    }

    public function getValueSelectOptions(): array
    {
        $this->_prepareValueOptions();

        return $this->getData('value_select_options') ?: [];
    }

    public function getValueAfterElementHtml(): string
    {
        $html = '';

        switch ($this->getAttribute()) {
            case 'sku':
            case 'category_ids':
                $image = $this->assetRepo->getUrl('images/rule_chooser_trigger.gif');
                break;
        }

        if (!empty($image)) {
            $html = '' .
            '<a href="javascript:void(0)" class="rule-chooser-trigger">' .
                '<img src="' . $image . '" alt="" class="v-middle rule-chooser-trigger" title="' . (string)__('Open Chooser') . '" />' .
            '</a>';
        }

        return $html;
    }

    public function getAttributeElement(): DataObject
    {
        $element = parent::getAttributeElement();
        $element->setShowAsText(true);

        return $element;
    }

    public function collectValidatedAttributes(Collection $productCollection): self
    {
        $attribute = $this->getAttribute();

        if (!in_array($attribute, ['category_ids', 'qty', 'final_price', 'price_diff', 'percent_discount', 'set_as_new', 'stock_status', 'is_salable'])) {
            if ($this->getAttributeObject()->isScopeGlobal()) {
                $attributes             = $this->getRule()->getCollectedAttributes();
                $attributes[$attribute] = true;

                $this->getRule()->setCollectedAttributes($attributes);
                $productCollection->addAttributeToSelect($attribute, 'left');
            } else {
                $this->_entityAttributeValues = $productCollection->getAllAttributeValues($attribute);
            }
        } elseif (isset($this->extraRulesPool[$attribute])) {
            foreach ($this->extraRulesPool[$attribute]->getExtraAttributesToSelect() as $attrCode) {
                $productCollection->addAttributeToSelect($attrCode, 'left');
            }
        }

        return $this;
    }

    public function getInputType(): string
    {
        if (isset($this->extraRulesPool[$this->getAttribute()])) {
            return $this->extraRulesPool[$this->getAttribute()]->getInputType();
        }

        switch ($this->getAttributeObject()->getFrontendInput()) {
            case 'select':
                return 'select';

            case 'multiselect':
                return 'multiselect';

            case 'boolean':
                return 'boolean';

            case 'date':
                return 'date';

            default:
                return 'string';
        }
    }

    public function getValueElementType(): string
    {
        if (isset($this->extraRulesPool[$this->getAttribute()])) {
            return $this->extraRulesPool[$this->getAttribute()]->getValueElementType();
        }

        if (!is_object($this->getAttributeObject())) {
            return 'text';
        }

        switch ($this->getAttributeObject()->getFrontendInput()) {
            case 'select':
            case 'boolean':
                return 'select';

            case 'multiselect':
                return 'multiselect';

            case 'date':
                return 'date';

            default:
                return 'text';
        }
    }

    public function getValueElementChooserUrl(): string
    {
        $url = false;

        switch ($this->getAttribute()) {
            case 'sku':
            case 'category_ids':
                $url = 'catalog_rule/promo_widget/chooser/attribute/' . $this->getAttribute();
                if ($this->getJsFormObject()) {
                    $url .= '/form/' . $this->getJsFormObject();
                } else {
                    $url .= '/form/rule_conditions_fieldset'; //@dva fixed js error in sku grid. not sure about.
                }
                break;
        }

        return $url !== false ? $this->backendUrlManager->getUrl($url) : '';
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getExplicitApply(): bool
    {
        switch ($this->getAttribute()) {
            case 'sku':
            case 'category_ids':
                return true;
        }

        if (is_object($this->getAttributeObject())) {
            switch ($this->getAttributeObject()->getFrontendInput()) {
                case 'date':
                    return true;
                default:
                    break;
            }
        }
        return false;
    }

    /**
     * @param array $arr
     * @return ProductRule
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function loadArray($arr)
    {
        $this->setAttribute(isset($arr['attribute']) ? $arr['attribute'] : false);
        $attribute = $this->getAttributeObject();

        if ($attribute && $attribute->getBackendType() == 'decimal') {
            if (isset($arr['value'])) {
                if (
                    !empty($arr['operator'])
                    && in_array($arr['operator'], ['!()', '()'])
                    && false !== strpos($arr['value'], ',')
                ) {
                    $tmp = [];

                    foreach (explode(',', $arr['value']) as $value) {
                        $tmp[] = $this->localeFormat->getNumber($value);
                    }

                    $arr['value'] = implode(',', $tmp);
                } else {
                    $arr['value'] = $this->localeFormat->getNumber($arr['value']);
                }
            } else {
                $arr['value'] = false;
            }

            $arr['is_value_parsed'] = isset($arr['is_value_parsed'])
                ? $this->localeFormat->getNumber($arr['is_value_parsed'])
                : false;
        }

        return parent::loadArray($arr);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function validate(AbstractModel $object): bool
    {
        $attrCode = $this->getAttribute();

        if (isset($this->extraRulesPool[$attrCode])) { //validate extra product condition
            return $this->extraRulesPool[$attrCode]->validate($object, $this);
        } elseif (!isset($this->_entityAttributeValues[(int) $object->getId()])) {
            $attr = $object->getResource()->getAttribute($attrCode);

            if ($attr && $attr->getBackendType() == 'datetime') {
                $productDateValue = $object->getData($attrCode);

                if ($productDateValue === null || $productDateValue === '') {
                    return false;
                }

                try {
                    $ruleValue = $this->getValue();

                    if (!is_int($ruleValue)) {
                        $this->setValue(strtotime((string)$ruleValue));
                    }

                    $value = strtotime((string)$productDateValue);

                    return $this->validateAttribute($value);
                } catch (\Exception $e) {
                    return false;
                }
            }

            if ($attr && $attr->getFrontendInput() == 'multiselect') {
                $value = $object->getData($attrCode);
                $value = strlen((string)$value) ? explode(',', $value) : [];

                return $this->validateAttribute($value);
            }

            if ($attr && $attr->getFrontendInput() == 'select') {
                $attributeValue = $object->getData($this->getAttribute());

                return $this->validateAttribute($attributeValue);
            }

            if ($attr && $attrCode == 'tier_price') {
                $object->load($object->getId());

                if ($priceData = $object->getData($attrCode)) {
                    foreach ($priceData as $tierPrice) {
                        if (!isset($tierPrice['price'])) {
                            continue;
                        }

                        if ($result = $this->validateAttribute($tierPrice['price'])) {
                            return $result;
                        }
                    }
                }
            }

            return parent::validate($object);
        } else {
            $result = false; // any valid value will set it to TRUE
            // remember old attribute state
            $oldAttrValue = $object->hasData($attrCode) ? $object->getData($attrCode) : null;

            $storeId = (int)$object->getStoreId();
            $attributeValues = $this->_entityAttributeValues[$object->getId()];
            $attr = $object->getResource()->getAttribute($attrCode);

            if (isset($attributeValues[$storeId])) {
                $value = $attributeValues[$storeId];
            } elseif (isset($attributeValues[0])) {
                $value = $attributeValues[0];
            } else {
                $value = $attr->getDefaultValue();
            }

            if ($attr && $attr->getBackendType() == 'datetime') {
                if ($value === null || $value === '') {
                    return false;
                }

                try {
                    $ruleValue = $this->getValue();

                    if (!is_int($ruleValue)) {
                        $this->setValue(strtotime((string)$ruleValue));
                    }

                    $value = strtotime((string)$value);
                } catch (\Exception $e) {
                    return false;
                }
            } elseif ($attr && $attr->getFrontendInput() == 'multiselect') {
                $value = strlen((string)$value) ? explode(',', $value) : [];
            }

            $object->setData($attrCode, $value);
            $result |= parent::validate($object);

            if ($oldAttrValue === null) {
                $object->unsetData($attrCode);
            } else {
                $object->setData($attrCode, $oldAttrValue);
            }

            return (bool)$result;
        }
    }
}
