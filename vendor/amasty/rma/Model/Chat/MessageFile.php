<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Chat;

use Amasty\Rma\Api\Data\MessageFileInterface;
use Magento\Framework\Model\AbstractModel;

class MessageFile extends AbstractModel implements MessageFileInterface
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Amasty\Rma\Model\Chat\ResourceModel\MessageFile::class);
        $this->setIdFieldName(MessageFileInterface::MESSAGE_FILE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setMessageFileId($messageFileId)
    {
        return $this->setData(MessageFileInterface::MESSAGE_FILE_ID, (int)$messageFileId);
    }

    /**
     * @inheritdoc
     */
    public function getMessageFileId()
    {
        return (int)$this->_getData(MessageFileInterface::MESSAGE_FILE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setMessageId($messageId)
    {
        return $this->setData(MessageFileInterface::MESSAGE_ID, (int)$messageId);
    }

    /**
     * @inheritdoc
     */
    public function getMessageId()
    {
        return (int)$this->_getData(MessageFileInterface::MESSAGE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setFilepath($filepath)
    {
        return $this->setData(MessageFileInterface::FILEPATH, $filepath);
    }

    /**
     * @inheritdoc
     */
    public function getFilepath()
    {
        return $this->_getData(MessageFileInterface::FILEPATH);
    }

    /**
     * @inheritDoc
     */
    public function setFilename($filename)
    {
        return $this->setData(MessageFileInterface::FILENAME, $filename);
    }

    /**
     * @inheritDoc
     */
    public function getFilename()
    {
        return $this->_getData(MessageFileInterface::FILENAME);
    }

    /**
     * @inheritDoc
     */
    public function setUrlHash($urlHash)
    {
        return $this->setData(MessageFileInterface::URL_HASH, $urlHash);
    }

    /**
     * @inheritDoc
     */
    public function getUrlHash()
    {
        return $this->_getData(MessageFileInterface::URL_HASH);
    }
}
