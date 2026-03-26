<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Magento\DataServices\Observer;

use Magento\Customer\Model\Session as PersonalizationSession;
use Magento\Framework\Event\ObserverInterface;

/**
 * CustomerAddressDelete class
 *
 * @api
 */
class CustomerAddressDelete implements ObserverInterface
{
   /**
     * @var PersonalizationSession
     */
    private $personalizationSession;

    /**
     * @param PersonalizationSession $personalizationSession
     */
    public function __construct(
        PersonalizationSession $personalizationSession
    ) {
       $this->personalizationSession = $personalizationSession;
    }

    /**
     * Set user session for address delete
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->personalizationSession->setUserAction("remove-address");
    }
}
