<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Controller\Adminhtml\Tabs;

use Amasty\CustomTabs\Api\Data\TabsInterface;
use Amasty\CustomTabs\Model\Source\Type;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class MassDuplicate
 * @package Amasty\CustomTabs\Controller\Adminhtml\Tabs
 */
class MassDuplicate extends AbstractMassAction
{
    /**
     * {@inheritdoc}
     */
    protected function itemAction(TabsInterface $tab)
    {
        if ($tab->getType() == Type::CUSTOM) {
            $this->repository->duplicate($tab);
        } else {
            throw new LocalizedException(__('You can\'t duplicate default tab with ID %1.', $tab->getTabId()));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getSuccessMessage($collectionSize = 0)
    {
        return __('A total of %1 record(s) have been duplicated.', $collectionSize);
    }
}
