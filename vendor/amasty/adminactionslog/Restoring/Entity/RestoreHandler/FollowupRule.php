<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Admin Actions Log for Magento 2
 */

namespace Amasty\AdminActionsLog\Restoring\Entity\RestoreHandler;

use Amasty\AdminActionsLog\Api\Data\LogDetailInterface;
use Amasty\AdminActionsLog\Api\Data\LogEntryInterface;
use Amasty\AdminActionsLog\Logging\Entity\SaveHandler\Amasty\Followup\Model\Rule as FollowupRuleSaveHandler;

class FollowupRule extends AbstractHandler
{
    public function restore(LogEntryInterface $logEntry, array $logDetails): void
    {
        if (empty($logDetails)) {
            return;
        }

        $element = $this->getModelObject($logEntry, current($logDetails));
        /** @var LogDetailInterface $logDetail */
        foreach ($logDetails as $logDetail) {
            $oldValue = $logDetail->getOldValue();
            $elementKey = $logDetail->getName();

            if (in_array($elementKey, FollowupRuleSaveHandler::ARRAY_VALUE_DATA_KEYS)) {
                $oldValue = (string)$oldValue !== '' ? explode(',', (string)$oldValue) : [];
            }

            $element->setData($elementKey, $oldValue ?? '');
        }

        $this->setRestoreActionFlag($element);
        $element->save();
    }
}
