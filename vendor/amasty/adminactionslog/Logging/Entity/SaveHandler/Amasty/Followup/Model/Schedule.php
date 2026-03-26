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
use Amasty\Followup\Model\Schedule as FollowupRuleSchedule;
use Magento\Framework\Model\AbstractModel;

class Schedule extends Common
{
    public const CATEGORY = 'amasty_followup/rule/edit';

    public function getLogMetadata(MetadataInterface $metadata): array
    {
        /** @var FollowupRuleSchedule $schedule */
        $schedule = $metadata->getObject();

        return [
            LogEntry::ITEM => __(
                'Schedule #%1 for Follow Up Email Rule #%2',
                (int)$schedule->getId(),
                (int)$schedule->getRuleId()
            ),
            LogEntry::CATEGORY => self::CATEGORY,
            LogEntry::CATEGORY_NAME => __('Follow Up Email Rule Schedule'),
            LogEntry::ELEMENT_ID => (int)$schedule->getId(),
            LogEntry::VIEW_ELEMENT_ID => (int)$schedule->getRuleId(),
        ];
    }

    public function processBeforeSave($object): array
    {
        if (!$object instanceof AbstractModel) {
            return [];
        }

        $rule = clone $object;
        $rule->load($object->getId());

        return $this->filterObjectData((array)$rule->getData());
    }
}
