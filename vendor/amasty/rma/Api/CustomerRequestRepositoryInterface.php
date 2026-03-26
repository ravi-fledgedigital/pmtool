<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Api;

/**
 * Interface CustomerRequestRepositoryInterface
 */
interface CustomerRequestRepositoryInterface
{
    /**
     * @param \Amasty\Rma\Api\Data\RequestInterface $request
     * @param string $secretKey
     *
     * @return \Amasty\Rma\Api\Data\RequestInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function create(\Amasty\Rma\Api\Data\RequestInterface $request, $secretKey = '');

    /**
     * @param int $requestId
     * @param int $customerId
     *
     * @return \Amasty\Rma\Api\Data\RequestInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($requestId, $customerId);

    /**
     * @param string $hash
     *
     * @return \Amasty\Rma\Api\Data\RequestInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByHash($hash);

    /**
     * @param string|int $requestIdHash
     * @param int $customerId
     *
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function closeRequest($requestIdHash, $customerId = 0);

    /**
     * @param string $hash
     * @param \Amasty\Rma\Api\Data\TrackingInterface $tracking
     *
     * @return \Amasty\Rma\Api\Data\TrackingInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function saveTracking($hash, \Amasty\Rma\Api\Data\TrackingInterface $tracking);

    /**
     * @param string $hash
     * @param int $trackingId
     *
     * @return \Amasty\Rma\Api\Data\TrackingInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function removeTracking($hash, $trackingId);

    /**
     * @param string $hash
     * @param int $rating
     * @param string $ratingComment
     *
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function saveRating($hash, $rating, $ratingComment);

    /**
     * @return \Amasty\Rma\Api\Data\RequestInterface
     */
    public function getEmptyRequestModel();

    /**
     * @return \Amasty\Rma\Api\Data\RequestItemInterface
     */
    public function getEmptyRequestItemModel();

    /**
     * @return \Amasty\Rma\Api\Data\TrackingInterface
     */
    public function getEmptyTrackingModel();
}
