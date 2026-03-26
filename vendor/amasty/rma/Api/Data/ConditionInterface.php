<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Api\Data;

interface ConditionInterface
{
    /**#@+
     * Constants defined for keys of data array
     */
    public const CONDITION_ID = 'condition_id';
    public const TITLE = 'title';
    public const STATUS = 'status';
    public const POSITION = 'position';
    public const STORES = 'stores';
    public const LABEL = 'label';
    public const IS_DELETED = 'is_deleted';
    /**#@-*/

    /**
     * @param int $conditionId
     *
     * @return \Amasty\Rma\Api\Data\ConditionInterface
     */
    public function setConditionId($conditionId);

    /**
     * @return int
     */
    public function getConditionId();

    /**
     * @param string $title
     *
     * @return \Amasty\Rma\Api\Data\ConditionInterface
     */
    public function setTitle($title);

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @param int $status
     *
     * @return \Amasty\Rma\Api\Data\ConditionInterface
     */
    public function setStatus($status);

    /**
     * @return int
     */
    public function getStatus();

    /**
     * @param int $position
     *
     * @return \Amasty\Rma\Api\Data\ConditionInterface
     */
    public function setPosition($position);

    /**
     * @return int
     */
    public function getPosition();

    /**
     * @param \Amasty\Rma\Api\Data\ConditionStoreInterface[]
     *
     * @return \Amasty\Rma\Api\Data\ConditionInterface
     */
    public function setStores($stores);

    /**
     * @return \Amasty\Rma\Api\Data\ConditionStoreInterface[]
     */
    public function getStores();

    /**
     * @param string $label
     *
     * @return \Amasty\Rma\Api\Data\ConditionInterface
     */
    public function setLabel($label);

    /**
     * @return string
     */
    public function getLabel();

    /**
     * @param bool $isDeleted
     *
     * @return \Amasty\Rma\Api\Data\ConditionInterface
     */
    public function setIsDeleted($isDeleted);

    /**
     * @return bool
     */
    public function getIsDeleted();
}
