<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Api;

interface ReturnRulesRepositoryInterface
{
    /**
     * @param \Amasty\Rma\Api\Data\ReturnRulesInterface $rule
     *
     * @return \Amasty\Rma\Api\Data\ReturnRulesInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Amasty\Rma\Api\Data\ReturnRulesInterface $rule);

    /**
     * @param int $ruleId
     *
     * @return \Amasty\Rma\Api\Data\ReturnRulesInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($ruleId);

    /**
     * @return \Amasty\Rma\Api\Data\ReturnRulesInterface[]
     */
    public function getActiveRules();

    /**
     * @param \Amasty\Rma\Api\Data\ReturnRulesInterface $rule
     *
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(\Amasty\Rma\Api\Data\ReturnRulesInterface $rule);

    /**
     * @param int $ruleId
     *
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById($ruleId);

    /**
     * @return \Amasty\Rma\Api\Data\ReturnRulesInterface
     */
    public function getEmptyRuleModel();

    /**
     * @return \Amasty\Rma\Api\Data\ReturnRulesWebsitesInterface
     */
    public function getEmptyRuleWebsiteModel();

    /**
     * @return \Amasty\Rma\Api\Data\ReturnRulesCustomerGroupsInterface
     */
    public function getEmptyRuleCustomerGroupModel();

    /**
     * @return \Amasty\Rma\Api\Data\ReturnRulesResolutionsInterface
     */
    public function getEmptyRuleResolutionModel();

    /**
     * @param int $ruleId
     *
     * @return string[]
     */
    public function getResolutionsByRuleId($ruleId);

    /**
     * @param int $ruleId
     *
     * @return string[]
     */
    public function getWebsitesByRuleId($ruleId);

    /**
     * @param int $ruleId
     *
     * @return string[]
     */
    public function getCustomerGroupsByRuleId($ruleId);

    /**
     * @param int $resolutionId
     * @param int $ruleId
     *
     * @return \Amasty\Rma\Api\Data\ReturnRulesResolutionsInterface
     */
    public function getRuleResolution($resolutionId, $ruleId);
}
