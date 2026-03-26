<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin\CustomerBalance\Model\Creditmemo;

use Magento\AdminGws\Model\ForceWhitelistRegistry;
use Magento\Customer\Model\Customer;
use Magento\CustomerBalance\Model\Creditmemo\Balance;
use Magento\Sales\Model\Order\Creditmemo;

class BalancePlugin
{
    /**
     * @var ForceWhitelistRegistry
     */
    private ForceWhitelistRegistry $forceWhitelistRegistry;

    /**
     * @param ForceWhitelistRegistry $forceWhitelistRegistry
     */
    public function __construct(ForceWhitelistRegistry $forceWhitelistRegistry)
    {
        $this->forceWhitelistRegistry = $forceWhitelistRegistry;
    }

    /**
     * Before customer balance save processing.
     *
     * @param Balance $subject
     * @param Creditmemo $creditmemo
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(Balance $subject, Creditmemo $creditmemo): void
    {
        $this->forceWhitelistRegistry->forceAllowLoading(Customer::class);
    }

    /**
     * After customer balance save processing.
     *
     * @param Balance $subject
     * @param mixed $result
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(Balance $subject, mixed $result)
    {
        $this->forceWhitelistRegistry->restore(Customer::class);

        return $result;
    }
}
