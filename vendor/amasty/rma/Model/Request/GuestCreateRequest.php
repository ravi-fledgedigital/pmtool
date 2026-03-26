<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Request;

use Amasty\Rma\Api\Data\GuestCreateRequestInterface;
use Magento\Framework\Model\AbstractModel;

class GuestCreateRequest extends AbstractModel implements GuestCreateRequestInterface
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Amasty\Rma\Model\Request\ResourceModel\GuestCreateRequest::class);
        $this->setIdFieldName(GuestCreateRequestInterface::CREATE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCreateId($createId)
    {
        return $this->setData(GuestCreateRequestInterface::CREATE_ID, (int)$createId);
    }

    /**
     * @inheritDoc
     */
    public function getCreateId()
    {
        return (int)$this->_getData(GuestCreateRequestInterface::CREATE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderId($orderId)
    {
        return $this->setData(GuestCreateRequestInterface::ORDER_ID, (int)$orderId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderId()
    {
        return (int)$this->_getData(GuestCreateRequestInterface::ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setBillingLastName($billingLastName)
    {
        return $this->setData(GuestCreateRequestInterface::BILLING_LAST_NAME, $billingLastName);
    }

    /**
     * @inheritDoc
     */
    public function getBillingLastName()
    {
        return $this->_getData(GuestCreateRequestInterface::BILLING_LAST_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setEmail($email)
    {
        return $this->setData(GuestCreateRequestInterface::EMAIL, $email);
    }

    /**
     * @inheritDoc
     */
    public function getEmail()
    {
        return $this->_getData(GuestCreateRequestInterface::EMAIL);
    }

    /**
     * @inheritDoc
     */
    public function setZip($zip)
    {
        return $this->setData(GuestCreateRequestInterface::ZIP, $zip);
    }

    /**
     * @inheritDoc
     */
    public function getZip()
    {
        return $this->_getData(GuestCreateRequestInterface::ZIP);
    }

    /**
     * @inheritDoc
     */
    public function setSecretCode($secretCode)
    {
        return $this->setData(GuestCreateRequestInterface::SECRET_CODE, $secretCode);
    }

    /**
     * @inheritDoc
     */
    public function getSecretCode()
    {
        return $this->_getData(GuestCreateRequestInterface::SECRET_CODE);
    }
}
