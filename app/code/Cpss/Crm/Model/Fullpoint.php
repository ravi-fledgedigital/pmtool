<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cpss\Crm\Model;

/**
 * Class Fullpoint
 *
 * @method \Magento\Quote\Api\Data\PaymentMethodExtensionInterface getExtensionAttributes()
 *
 * @api
 * @since 100.0.2
 */
class Fullpoint extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_FULLPOINT_CODE = 'fullpoint';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_FULLPOINT_CODE;

    /**
     * @var string
     */
    protected $_formBlockType = \Cpss\Crm\Block\Form\Fullpoint::class;

    /**
     * @var string
     */
    protected $_infoBlockType = \Cpss\Crm\Block\Info\Fullpoint::class;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

    /**
     * @return string
     */
    public function getPayableTo()
    {
        return $this->getConfigData('payable_to');
    }

    /**
     * @return string
     */
    public function getMailingAddress()
    {
        return $this->getConfigData('mailing_address');
    }
}
