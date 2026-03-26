<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin\Customer\Ui\Component\Listing\Column;

use Magento\AdminGws\Model\Role;
use Magento\Customer\Ui\Component\Listing\Column\GroupActions;

/**
 * Check if certain customer group is allowed to see delete button
 */
class CanHideDeleteButton
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
     * After plugin to determine if current customer is allowed to view delete button
     *
     * @param GroupActions $subject
     * @param bool $result
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCanHideDeleteButton(
        GroupActions $subject,
        bool $result
    ): bool {
        if (!$this->role->getIsAll()) {
            $result = true;
        }
        return $result;
    }
}
