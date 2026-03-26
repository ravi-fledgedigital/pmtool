<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model\Update;

use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\Staging\Model\VersionHistoryInterface;

class VersionHistory implements VersionHistoryInterface, ResetAfterRequestInterface
{
    /**
     * @var Flag|null
     */
    protected $flag;

    /**
     * @var FlagFactory
     */
    protected $flagFactory;

    /**
     * @param FlagFactory $flagFactory
     */
    public function __construct(
        \Magento\Staging\Model\Update\FlagFactory $flagFactory
    ) {
        $this->flagFactory = $flagFactory;
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->flag = null;
    }

    /**
     * Retrieve maximum versions in DB
     *
     * @return int
     */
    public function getMaximumInDB()
    {
        return (int)$this->getFlag()->getMaximumVersionsInDb();
    }

    /**
     * Set maximum versions in DB
     *
     * @param int $maximumVersions
     * @return void
     */
    public function setMaximumInDB($maximumVersions)
    {
        $this->getFlag()->setMaximumVersionsInDb($maximumVersions);
        $this->getFlag()->save();
    }

    /**
     * Get current version id
     *
     * @return int|string
     */
    public function getCurrentId()
    {
        return $this->getFlag()->getCurrentVersionId();
    }

    /**
     * Set current version id
     *
     * @param int $versionId
     * @return void
     */
    public function setCurrentId($versionId)
    {
        $this->getFlag()->setCurrentVersionId($versionId);
        $this->getFlag()->save();
    }

    /**
     * Retrieve flag
     *
     * @return \Magento\Staging\Model\Update\Flag
     */
    protected function getFlag()
    {
        if (!$this->flag) {
            $this->flag = $this->flagFactory->create([]);
            $this->flag->loadSelf();
        }
        return $this->flag;
    }
}
