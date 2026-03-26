<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin\Customer\Block\Adminhtml\Group;

use Magento\AdminGws\Model\Role;
use Magento\Customer\Block\Adminhtml\Group\AddCustomerGroupButton;

/**
 * Check if certain customer group is allowed to add customer group details
 */
class IsCustomerAllowedToAddCustomerGroupButtonPlugin
{
    /**
     * @var Role
     */
    private $role;

    /**
     * @param Role $role
     */
    public function __construct(Role $role)
    {
        $this->role = $role;
    }

    /**
     * After plugin to determine if current customer is allowed to add new customer group
     *
     * @param AddCustomerGroupButton $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetButtonData(
        AddCustomerGroupButton $subject,
        array $result
    ): array {
        if (!$this->role->getIsAll()) {
            $result = [];
        }
        return $result;
    }
}
