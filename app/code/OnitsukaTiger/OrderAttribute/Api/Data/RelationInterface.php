<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Api\Data;

/**
 * Attributes Dependency
 */
interface RelationInterface
{
    /**#@+
     * Constants defined for keys of data array
     */
    public const RELATION_ID = 'relation_id';

    public const NAME = 'name';
    /**#@-*/

    /**
     * Returns Relation ID
     *
     * @return int
     */
    public function getRelationId();

    /**
     * @param int $relationId
     *
     * @return $this
     */
    public function setRelationId($relationId);

    /**
     * Returns Relation name
     *
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name);

    /**
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\RelationDetailInterface[]
     */
    public function getDetails();

    /**
     * @param \OnitsukaTiger\OrderAttribute\Api\Data\RelationDetailInterface[] $relationDetails
     *
     * @return $this
     */
    public function setDetails($relationDetails);
}
