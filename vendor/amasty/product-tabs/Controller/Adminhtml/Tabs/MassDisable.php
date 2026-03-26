<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Product Tabs for Magento 2
 */

namespace Amasty\CustomTabs\Controller\Adminhtml\Tabs;

use Amasty\CustomTabs\Api\Data\TabsInterface;

/**
 * Class MassDelete
 */
class MassDisable extends AbstractMassAction
{
    /**
     * {@inheritdoc}
     */
    protected function itemAction(TabsInterface $tab)
    {
        $tab->setStatus(0);
        $this->repository->save($tab);
    }
}
