<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Api\Data;

/**
 * Interface TrackingInterface
 */
interface TrackingInterface
{
    /**#@+
     * Constants defined for keys of data array
     */
    public const TRACKING_ID = 'tracking_id';
    public const REQUEST_ID = 'request_id';
    public const TRACKING_CODE = 'tracking_code';
    public const TRACKING_NUMBER = 'tracking_number';
    public const IS_CUSTOMER = 'is_customer';
    /**#@-*/

    /**
     * @param int $trackingId
     *
     * @return \Amasty\Rma\Api\Data\TrackingInterface
     */
    public function setTrackingId($trackingId);

    /**
     * @return int
     */
    public function getTrackingId();

    /**
     * @param int $requestId
     *
     * @return \Amasty\Rma\Api\Data\TrackingInterface
     */
    public function setRequestId($requestId);

    /**
     * @return int
     */
    public function getRequestId();

    /**
     * @param string $trackingCode
     *
     * @return \Amasty\Rma\Api\Data\TrackingInterface
     */
    public function setTrackingCode($trackingCode);

    /**
     * @return string
     */
    public function getTrackingCode();

    /**
     * @param string $trackingNumber
     *
     * @return \Amasty\Rma\Api\Data\TrackingInterface
     */
    public function setTrackingNumber($trackingNumber);

    /**
     * @return string
     */
    public function getTrackingNumber();

    /**
     * @param bool $isCustomer
     *
     * @return \Amasty\Rma\Api\Data\TrackingInterface
     */
    public function setIsCustomer($isCustomer);

    /**
     * @return bool
     */
    public function isCustomer();
}
