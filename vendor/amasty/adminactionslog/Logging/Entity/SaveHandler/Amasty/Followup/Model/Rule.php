<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Admin Actions Log for Magento 2
 */

namespace Amasty\AdminActionsLog\Logging\Entity\SaveHandler\Amasty\Followup\Model;

use Amasty\AdminActionsLog\Api\Logging\MetadataInterface;
use Amasty\AdminActionsLog\Logging\Entity\SaveHandler\Common;
use Amasty\AdminActionsLog\Model\LogEntry\LogEntry;
use Amasty\Followup\Model\Rule as FollowupRule;
use Magento\Framework\Model\AbstractModel;

class Rule extends Common
{
    public const CATEGORY = 'amasty_followup/rule/edit';

    /**
     * @var array
     */
    public const ARRAY_VALUE_DATA_KEYS = [
        'customer_group_ids',
        'store_ids',
        'segment_ids'
    ];

    /**
     * @var string[]
     */
    protected $dataKeysIgnoreList = [
        'form_key',
        'isEditForm',
        'isNotSingleStoreMode',
        'actions_serialized'
    ];

    public function getLogMetadata(MetadataInterface $metadata): array
    {
        /** @var FollowupRule $rule */
        $rule = $metadata->getObject();

        return [
            LogEntry::ITEM => $rule->getName(),
            LogEntry::CATEGORY => self::CATEGORY,
            LogEntry::CATEGORY_NAME => __('Follow Up Email Rule'),
            LogEntry::ELEMENT_ID => (int)$rule->getId(),
        ];
    }

    public function processBeforeSave($object): array
    {
        if (!$object instanceof AbstractModel) {
            return [];
        }

        $data = $this->prepareData((array)$object->getOrigData());

        return $this->filterObjectData($data);
    }

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
        foreach (self::ARRAY_VALUE_DATA_KEYS as $key) {
            $data[$key] = implode(',', $data[$key] ?? []);
        }

        return $data;
    }
}
