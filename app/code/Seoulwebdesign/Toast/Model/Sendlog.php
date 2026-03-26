<?php

namespace Seoulwebdesign\Toast\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class Sendlog extends AbstractModel implements IdentityInterface
{
    public const CACHE_TAG = 'seoulwebdesign_toast_sendlog';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;
    /**
     * @var string
     */
    protected $_eventPrefix = self::CACHE_TAG;

    /**
     * Get cache identities
     *
     * @return string[]
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
