<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Api;

use OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface;

/**
 * Interface AttributeRepositoryInterface
 * @api
 */
interface CheckoutAttributeRepositoryInterface
{
    /**
     * Retrieve specific attribute by id
     *
     * @param int $attributeId
     * @return CheckoutAttributeInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function getById($attributeId);

    /**
     * Retrieve all attributes for entity type
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Catalog\Api\Data\ProductAttributeSearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Retrieve specific attribute
     *
     * @param string $attributeCode
     * @return CheckoutAttributeInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($attributeCode);

    /**
     * Save attribute data
     *
     * @param CheckoutAttributeInterface $attribute
     * @return CheckoutAttributeInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function save(CheckoutAttributeInterface $attribute);

    /**
     * Delete Attribute
     *
     * @param CheckoutAttributeInterface $attribute
     * @return bool True if the entity was deleted (always true)
     * @throws \Magento\Framework\Exception\StateException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function delete(CheckoutAttributeInterface $attribute);

    /**
     * Delete Attribute by id
     *
     * @param string $attributeCode
     * @return bool
     * @throws \Magento\Framework\Exception\StateException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteById($attributeCode);
}
