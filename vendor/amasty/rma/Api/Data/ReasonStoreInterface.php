<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Api\Data;

/**
 * Interface ReasonStoreInterface
 */
interface ReasonStoreInterface
{
    /**#@+
     * Constants defined for keys of data array
     */
    public const REASON_STORE_ID = 'reason_store_id';
    public const REASON_ID = 'reason_id';
    public const STORE_ID = 'store_id';
    public const LABEL = 'label';
    /**#@-*/

    /**
     * @param int $reasonStoreId
     *
     * @return \Amasty\Rma\Api\Data\ReasonStoreInterface
     */
    public function setReasonStoreId($reasonStoreId);

    /**
     * @return int
     */
    public function getReasonStoreId();

    /**
     * @param int $reasonId
     *
     * @return \Amasty\Rma\Api\Data\ReasonStoreInterface
     */
    public function setReasonId($reasonId);

    /**
     * @return int
     */
    public function getReasonId();

    /**
     * @param int $storeId
     *
     * @return \Amasty\Rma\Api\Data\ReasonStoreInterface
     */
    public function setStoreId($storeId);

    /**
     * @return int
     */
    public function getStoreId();

    /**
     * @param string $label
     *
     * @return \Amasty\Rma\Api\Data\ReasonStoreInterface
     */
    public function setLabel($label);

    /**
     * @return string
     */
    public function getLabel();
}
