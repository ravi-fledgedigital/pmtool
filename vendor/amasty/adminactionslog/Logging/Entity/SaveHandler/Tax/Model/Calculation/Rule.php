<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Admin Actions Log for Magento 2
 */

namespace Amasty\AdminActionsLog\Logging\Entity\SaveHandler\Tax\Model\Calculation;

use Amasty\AdminActionsLog\Api\Logging\MetadataInterface;
use Amasty\AdminActionsLog\Logging\Entity\SaveHandler\Common;
use Amasty\AdminActionsLog\Model\LogEntry\LogEntry;
use Amasty\AdminActionsLog\Model\OptionSource\LogEntryTypes;
use Magento\Tax\Model\Calculation\Rule as TaxRule;
use Magento\Framework\Model\AbstractModel;

class Rule extends Common
{
    public const CATEGORY = 'tax/rule/edit';

    /**
     * @var string[]
     */
    protected $dataKeysIgnoreList = [
        'id'
    ];

    /**
     * @var array
     */
    public const ARRAY_VALUE_DATA_KEYS = [
        'tax_rate_ids',
        'customer_tax_class_ids',
        'product_tax_class_ids'
    ];

    public function getLogMetadata(MetadataInterface $metadata): array
    {
        /** @var TaxRule $taxRule */
        $taxRule = $metadata->getObject();
        $type = $taxRule->isObjectNew() ? LogEntryTypes::TYPE_NEW : LogEntryTypes::TYPE_EDIT;

        return [
            LogEntry::TYPE => $type,
            LogEntry::ITEM => __('Tax Rule #%1', $taxRule->getId()),
            LogEntry::CATEGORY => self::CATEGORY,
            LogEntry::CATEGORY_NAME => __('Tax Rule'),
            LogEntry::ELEMENT_ID => (int)$taxRule->getId(),
            LogEntry::PARAMETER_NAME => 'rule'
        ];
    }

    /**
     * @param TaxRule $object
     * @return array
     */
    public function processBeforeSave($object): array
    {
        if (!$object instanceof AbstractModel
            || $object->isObjectNew()
        ) {
            return [];
        }

        $rule = clone $object;
        $rule->load($object->getId());
        $data = $this->prepareData($rule);

        return $this->filterObjectData($data);
    }

    /**
     * @param TaxRule $object
     * @return array
     */
    public function processAfterSave($object): array
    {
        if (!$object instanceof AbstractModel) {
            return [];
        }

        $data = $this->prepareData($object);

        return $this->filterObjectData($data);
    }

    private function prepareData(TaxRule $rule): array
    {
        $data = $rule->getData();

        foreach (self::ARRAY_VALUE_DATA_KEYS as $key) {
            $value = $data[$key] ?? $rule->getDataUsingMethod($key);
            $data[$key] = is_array($value) ? implode(',', $value) : (string)$value;
        }

        return $data;
    }
}
