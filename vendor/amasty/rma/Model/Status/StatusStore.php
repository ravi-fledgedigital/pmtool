<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Status;

use Amasty\Rma\Api\Data\StatusStoreInterface;
use Magento\Framework\Model\AbstractModel;

class StatusStore extends AbstractModel implements StatusStoreInterface
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Amasty\Rma\Model\Status\ResourceModel\StatusStore::class);
        $this->setIdFieldName(StatusStoreInterface::STATUS_STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStatusStoreId($statusStoreId)
    {
        return $this->setData(StatusStoreInterface::STATUS_STORE_ID, (int)$statusStoreId);
    }

    /**
     * @inheritdoc
     */
    public function getStatusStoreId()
    {
        return (int)$this->_getData(StatusStoreInterface::STATUS_STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStatusId($statusId)
    {
        return $this->setData(StatusStoreInterface::STATUS_ID, (int)$statusId);
    }

    /**
     * @inheritdoc
     */
    public function getStatusId()
    {
        return (int)$this->_getData(StatusStoreInterface::STATUS_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStoreId($storeId)
    {
        return $this->setData(StatusStoreInterface::STORE_ID, (int)$storeId);
    }

    /**
     * @inheritdoc
     */
    public function getStoreId()
    {
        return (int)$this->_getData(StatusStoreInterface::STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setLabel($label)
    {
        return $this->setData(StatusStoreInterface::LABEL, $label);
    }

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return $this->_getData(StatusStoreInterface::LABEL);
    }

    /**
     * @inheritdoc
     */
    public function setDescription($description)
    {
        return $this->setData(StatusStoreInterface::DESCRIPTION, $description);
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->_getData(StatusStoreInterface::DESCRIPTION);
    }

    /**
     * @inheritdoc
     */
    public function setIsSendEmailToCustomer($send)
    {
        return $this->setData(StatusStoreInterface::SEND_EMAIL_TO_CUSTOMER, (bool)$send);
    }

    /**
     * @inheritdoc
     */
    public function isSendEmailToCustomer()
    {
        return (bool)$this->_getData(StatusStoreInterface::SEND_EMAIL_TO_CUSTOMER);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerEmailTemplate($emailTemplate)
    {
        return $this->setData(StatusStoreInterface::CUSTOMER_EMAIL_TEMPLATE, (int)$emailTemplate);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerEmailTemplate()
    {
        return (int)$this->_getData(StatusStoreInterface::CUSTOMER_EMAIL_TEMPLATE);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerCustomText($customText)
    {
        return $this->setData(StatusStoreInterface::CUSTOMER_CUSTOM_TEXT, $customText);
    }

    /**
     * @inheritdoc
     */
    public function getCustomerCustomText()
    {
        return $this->_getData(StatusStoreInterface::CUSTOMER_CUSTOM_TEXT);
    }

    /**
     * @inheritdoc
     */
    public function setIsSendEmailToAdmin($send)
    {
        return $this->setData(StatusStoreInterface::SEND_EMAIL_TO_ADMIN, (bool)$send);
    }

    /**
     * @inheritdoc
     */
    public function isSendEmailToAdmin()
    {
        return (bool)$this->_getData(StatusStoreInterface::SEND_EMAIL_TO_ADMIN);
    }

    /**
     * @inheritDoc
     */
    public function setAdminEmailTemplate($emailTemplate)
    {
        return $this->setData(StatusStoreInterface::ADMIN_EMAIL_TEMPLATE, (int)$emailTemplate);
    }

    /**
     * @inheritDoc
     */
    public function getAdminEmailTemplate()
    {
        return (int)$this->_getData(StatusStoreInterface::ADMIN_EMAIL_TEMPLATE);
    }

    /**
     * @inheritdoc
     */
    public function setAdminCustomText($customText)
    {
        return $this->setData(StatusStoreInterface::ADMIN_CUSTOM_TEXT, $customText);
    }

    /**
     * @inheritdoc
     */
    public function getAdminCustomText()
    {
        return $this->_getData(StatusStoreInterface::ADMIN_CUSTOM_TEXT);
    }

    /**
     * @inheritdoc
     */
    public function setIsSendToChat($send)
    {
        return $this->setData(StatusStoreInterface::SEND_TO_CHAT, (bool)$send);
    }

    /**
     * @inheritdoc
     */
    public function isSendToChat()
    {
        return (bool)$this->_getData(StatusStoreInterface::SEND_TO_CHAT);
    }

    /**
     * @inheritdoc
     */
    public function setChatMessage($chatMessage)
    {
        return $this->setData(StatusStoreInterface::CHAT_MESSAGE, $chatMessage);
    }

    /**
     * @inheritdoc
     */
    public function getChatMessage()
    {
        return $this->_getData(StatusStoreInterface::CHAT_MESSAGE);
    }
}
