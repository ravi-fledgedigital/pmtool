<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\OptionSource;

use Magento\Framework\Authorization\PolicyInterface;
use Magento\Framework\Option\ArrayInterface;
use Magento\User\Model\ResourceModel\User\CollectionFactory;

class Manager implements ArrayInterface
{
    public const IS_RMA_MANAGER_ACL = 'Amasty_Rma::is_rma_manager';

    public function __construct(
        private readonly CollectionFactory $managerCollectionFactory,
        private readonly PolicyInterface $aclPolicy
    ) {
    }

    /**
     * @return array [['value' => value, 'label' => label], ...]
     */
    public function toOptionArray(): array
    {
        $optionArray = [];
        foreach ($this->toArray() as $value => $label) {
            $optionArray[] = ['value' => $value, 'label' => $label];
        }
        return $optionArray;
    }

    /**
     * @return string[] [key => value, ...]
     */
    public function toArray(): array
    {
        $result = [0 => __('Unassigned')];
        $managerCollection = $this->managerCollectionFactory->create();
        $managerCollection->addFieldToFilter('main_table.is_active', 1)//TODO is_active?
            ->addFieldToSelect(['user_id', 'username']);

        foreach ($managerCollection as $manager) {
            if ($this->aclPolicy->isAllowed($manager->getAclRole(), self::IS_RMA_MANAGER_ACL)) {
                $result[$manager->getId()] = $manager->getUserName();
            }
        }

        return $result;
    }
}
