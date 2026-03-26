<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Api;

/**
 * Interface RelationRepositoryInterface
 *
 * @api
 */
interface RelationRepositoryInterface
{
    /**
     * @param \OnitsukaTiger\OrderAttribute\Api\Data\RelationInterface $relation
     *
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\RelationInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\OnitsukaTiger\OrderAttribute\Api\Data\RelationInterface $relation);

    /**
     * @param int $relationId
     *
     * @return \OnitsukaTiger\OrderAttribute\Api\Data\RelationInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($relationId);

    /**
     * @param \OnitsukaTiger\OrderAttribute\Api\Data\RelationInterface $relation
     *
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(\OnitsukaTiger\OrderAttribute\Api\Data\RelationInterface $relation);

    /**
     * @param int $ruleId
     *
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById($ruleId);
}
