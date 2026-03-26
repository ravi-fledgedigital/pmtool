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
use Amasty\AdminActionsLog\Logging\Util\Ignore\ArrayFilter;
use Amasty\AdminActionsLog\Model\LogEntry\LogEntry;
use Amasty\AdminActionsLog\Model\OptionSource\LogEntryTypes;
use Amasty\Base\Model\Serializer;
use Magento\Framework\Model\AbstractModel;
use Magento\Tax\Model\Calculation\Rate as TaxRate;
use Magento\Tax\Model\Calculation\Rate\Title;

class Rate extends Common
{
    public const CATEGORY = 'tax/rate/edit';

    /**
     * @var string[]
     */
    protected $dataKeysIgnoreList = [
        'id'
    ];

    public function __construct(
        ArrayFilter\ScalarValueFilter $scalarValueFilter,
        ArrayFilter\KeyFilter $keyFilter,
        private readonly Serializer $serializer
    ) {
        parent::__construct($scalarValueFilter, $keyFilter);
    }

    public function getLogMetadata(MetadataInterface $metadata): array
    {
        /** @var TaxRate $taxRate */
        $taxRate = $metadata->getObject();
        $type = $taxRate->isObjectNew() ? LogEntryTypes::TYPE_NEW : LogEntryTypes::TYPE_EDIT;

        return [
            LogEntry::TYPE => $type,
            LogEntry::ITEM => __('Tax Rate #%1', $taxRate->getId()),
            LogEntry::CATEGORY => self::CATEGORY,
            LogEntry::CATEGORY_NAME => __('Tax Rate'),
            LogEntry::ELEMENT_ID => (int)$taxRate->getId(),
            LogEntry::PARAMETER_NAME => 'rate'
        ];
    }

    /**
     * @param TaxRate $object
     * @return array
     */
    public function processBeforeSave($object): array
    {
        if (!$object instanceof AbstractModel || $object->isObjectNew()) {
            return [];
        }

        $rate = clone $object;
        $rate->load($object->getId());

        $data = (array)$rate->getData();
        if (!isset($data['titles']) && $rate->getTitles()) {
            $data['titles'] = $rate->getTitles();
        }
        $data = $this->prepareData($data);

        return $this->filterObjectData($data);
    }

    /**
     * @param TaxRate $object
     * @return array
     */
    public function processAfterSave($object): array
    {
        if (!$object instanceof AbstractModel) {
            return [];
        }

        $data = $this->prepareData((array)$object->getData());

        return $this->filterObjectData($data);
    }

    private function prepareData(array $data): array
    {
        if (isset($data['titles'])) {
            $storeToTitleMap = [];
            foreach ($data['titles'] as $key => $title) {
                if ($title instanceof Title) {
                    $storeToTitleMap[$title->getStoreId()] = $title->getValue();
                } elseif (is_string($title)) {
                    $storeToTitleMap[$key] = $title;
                }
            }

            ksort($storeToTitleMap);
            $data['titles'] = $this->serializer->serialize($storeToTitleMap);
        }

        return $data;
    }
}
