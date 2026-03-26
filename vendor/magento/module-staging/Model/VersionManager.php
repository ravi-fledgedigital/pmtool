<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\Staging\Api\Data\UpdateInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class VersionManager implements ResetAfterRequestInterface
{
    /**
     * @var int
     */
    public const MAX_VERSION = 2147483647;

    /**
     * @var int
     */
    public const MIN_VERSION = 1;

    /**
     * @var string
     */
    public const PARAM_NAME = '___version';

    /**
     * Default version Id
     *
     * @var string
     */
    protected $currentVersionId;

    /**
     * @var UpdateFactory
     */
    protected $updateFactory;

    /**
     * @var \Magento\Staging\Api\UpdateRepositoryInterface
     */
    protected $updateRepository;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var UpdateInterface
     */
    protected $version;

    /**
     * @var int
     */
    protected $requestedTimestamp;

    /**
     * @var VersionHistoryInterface
     */
    protected $versionHistory;

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * VersionManager constructor.
     *
     * @param UpdateFactory $updateFactory
     * @param \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository
     * @param \Magento\Framework\App\RequestInterface $request
     * @param VersionHistoryInterface $versionHistory
     */
    public function __construct(
        UpdateFactory $updateFactory,
        \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository,
        \Magento\Framework\App\RequestInterface $request,
        VersionHistoryInterface $versionHistory
    ) {
        $this->updateFactory = $updateFactory;
        $this->updateRepository = $updateRepository;
        $this->request = $request;
        $this->versionHistory = $versionHistory;
    }

    /**
     * Set current version id
     *
     * @param int $versionId
     * @return void
     */
    public function setCurrentVersionId($versionId)
    {
        $this->currentVersionId = $versionId;
        $this->version = null;
    }

    /**
     * Retrieve version by requested data or return current version
     *
     * @return \Magento\Staging\Api\Data\UpdateInterface
     */
    public function getVersion()
    {
        if ($this->version && $this->version->getId() == $this->versionHistory->getCurrentId()) {
            return $this->version;
        }

        if ($this->getRequestedTimestamp()) {
            $version = $this->getVersionMaxIdByTime(
                $this->getRequestedTimestamp()
            );
        } else {
            $version = $this->getCurrentVersionId();
        }

        $this->version = $this->getVersionById($version);

        return $this->version;
    }

    /**
     * Get requested timestamp
     *
     * @return int|null
     */
    public function getRequestedTimestamp()
    {
        if ($this->requestedTimestamp) {
            return $this->requestedTimestamp;
        }

        $requestedTimestamp = $this->request->getParam(self::PARAM_NAME);

        if ($requestedTimestamp) {
            $this->requestedTimestamp = (int)$requestedTimestamp;
        }

        return $this->requestedTimestamp;
    }

    /**
     * @inheritdoc
     */
    public function getCurrentVersion()
    {
        return $this->getVersionById($this->getCurrentVersionId());
    }

    /**
     * Retrieve Deployment Config
     *
     * @deprecated 100.1.3
     * @see Nothing
     * @return DeploymentConfig
     */
    private function getDeploymentConfig()
    {
        if (!$this->deploymentConfig) {
            $this->deploymentConfig = ObjectManager::getInstance()->get(DeploymentConfig::class);
        }

        return $this->deploymentConfig;
    }

    /**
     * @inheritdoc
     */
    public function isPreviewVersion()
    {
        if (!$this->getDeploymentConfig()->isDbAvailable()) {
            return false;
        }

        return $this->getVersion()->getId() != $this->versionHistory->getCurrentId() || $this->getRequestedTimestamp();
    }

    /**
     * Get current version id
     *
     * @return mixed|string
     */
    protected function getCurrentVersionId()
    {
        if (!$this->currentVersionId) {
            return (int)$this->versionHistory->getCurrentId();
        }
        return $this->currentVersionId;
    }

    /**
     * Get version by id
     *
     * @param int $versionId
     * @return \Magento\Staging\Api\Data\UpdateInterface
     */
    protected function getVersionById($versionId)
    {
        try {
            return $this->updateRepository->get($versionId);
        } catch (NoSuchEntityException $e) {
            return $this->updateFactory->create()->setId(1);
        }
    }

    /**
     * Get version max id by time
     *
     * @param int $timestamp
     * @return mixed
     */
    protected function getVersionMaxIdByTime($timestamp)
    {
        return $this->updateRepository->getVersionMaxIdByTime($timestamp);
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->requestedTimestamp = null;
        $this->currentVersionId = null;
        $this->version = null;
    }
}
