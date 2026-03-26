<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Cpss\Crm\Block\Info;

class Fullpoint extends \Magento\Payment\Block\Info
{
    /**
     * @var string
     */
    protected $_payableTo;

    /**
     * @var string
     */
    protected $_mailingAddress;

    /**
     * @var string
     */
    protected $_template = 'Cpss_Crm::info/fullpoint.phtml';

    /**
     * Enter description here...
     *
     * @return string
     */
    public function getPayableTo()
    {
        if ($this->_payableTo === null) {
            $this->_convertAdditionalData();
        }
        return $this->_payableTo;
    }

    /**
     * Enter description here...
     *
     * @return string
     */
    public function getMailingAddress()
    {
        if ($this->_mailingAddress === null) {
            $this->_convertAdditionalData();
        }
        return $this->_mailingAddress;
    }

    /**
     * @deprecated 100.1.1
     * @return $this
     */
    protected function _convertAdditionalData()
    {
        $this->_payableTo = $this->getInfo()->getAdditionalInformation('payable_to');
        $this->_mailingAddress = $this->getInfo()->getAdditionalInformation('mailing_address');
        return $this;
    }

    /**
     * @return string
     */
    public function toPdf()
    {
        // $this->setTemplate('Cpss_Crm::info/pdf/checkmo.phtml');
        return $this->toHtml();
    }
}
