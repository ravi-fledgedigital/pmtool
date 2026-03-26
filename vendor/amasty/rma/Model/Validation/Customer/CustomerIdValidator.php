<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Validation\Customer;

class CustomerIdValidator
{
    public function isValid(int $orderCustomerId, int $currentCustomerId): bool
    {
        return $orderCustomerId === $currentCustomerId;
    }
}
