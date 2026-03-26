<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\ObjectManager;

class Data extends AbstractHelper
{
    /**
     * Maximal gift card code length according to database table definitions (longer codes are truncated)
     */
    public const GIFT_CARD_CODE_MAX_LENGTH = 255;

    /**
     * Instance of serializer.
     *
     * @var Json
     */
    private $serializer;

    /**
     * @param Context $context
     * @param Json|null $serializer
     */
    public function __construct(Context $context, Json $serializer = null)
    {
        parent::__construct($context);
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
    }

    /**
     * Unserialize and return gift card list from specified object
     *
     * @param DataObject $from
     * @return mixed
     */
    public function getCards(DataObject $from)
    {
        $value = $from->getGiftCards();
        if (!$value) {
            return [];
        }

        return $this->serializer->unserialize($value);
    }

    /**
     * Serialize and set gift card list to specified object
     *
     * @param DataObject $to
     * @param mixed $value
     * @return void
     */
    public function setCards(DataObject $to, $value)
    {
        $serializedValue = $this->serializer->serialize($value);
        $to->setGiftCards($serializedValue);
    }

    /**
     * Unserialize and return used gift card list from specified object
     *
     * @param DataObject $from
     * @return mixed
     */
    public function getUsedCards(DataObject $from)
    {
        $value = $from->getUsedGiftCards();
        if (!$value) {
            return [];
        }

        return $this->serializer->unserialize($value);
    }

    /**
     * Serialize and set used gift card list to specified object
     *
     * @param DataObject $to
     * @param mixed $value
     * @return void
     */
    public function setUsedCards(DataObject $to, $value)
    {
        $serializedValue = $this->serializer->serialize($value);
        $to->setUsedGiftCards($serializedValue);
    }

    /**
     * Unserialize and return unused gift card list from specified object
     *
     * @param DataObject $from
     * @return mixed
     */
    public function getUnusedCards(DataObject $from)
    {
        $value = $from->getUnusedGiftCards();
        if (!$value) {
            return [];
        }

        return $this->serializer->unserialize($value);
    }

    /**
     * Serialize and set unused gift card list to specified object
     *
     * @param DataObject $to
     * @param mixed $value
     * @return void
     */
    public function setUnusedCards(DataObject $to, $value)
    {
        $serializedValue = $this->serializer->serialize($value);
        $to->setUnusedGiftCards($serializedValue);
    }
}
