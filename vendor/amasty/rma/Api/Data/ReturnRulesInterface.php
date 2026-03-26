<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Api\Data;

interface ReturnRulesInterface
{
    /**#@+
     * Constants defined for keys of data array
     */
    public const ID = 'id';
    public const NAME = 'name';
    public const STATUS = 'status';
    public const PRIORITY = 'priority';
    public const DEFAULT_RESOLUTION = 'default_resolution';
    public const CONDITIONS_SERIALIZED = 'conditions_serialized';
    public const CUSTOMER_GROUPS = 'customer_groups';
    public const WEBSITES = 'websites';
    public const RESOLUTIONS = 'resolutions';
    /**#@-*/

    /**
     * @param int $id
     *
     * @return \Amasty\Rma\Api\Data\ReturnRulesInterface
     */
    public function setRuleId($id);

    /**
     * @return int
     */
    public function getRuleId();

    /**
     * @param string $name
     *
     * @return \Amasty\Rma\Api\Data\ReturnRulesInterface
     */
    public function setName($name);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param int $status
     *
     * @return \Amasty\Rma\Api\Data\ReturnRulesInterface
     */
    public function setStatus($status);

    /**
     * @return int
     */
    public function getStatus();

    /**
     * @param int $priority
     *
     * @return \Amasty\Rma\Api\Data\ReturnRulesInterface
     */
    public function setPriority($priority);

    /**
     * @return int
     */
    public function getPriority();

    /**
     * @param int $period
     *
     * @return \Amasty\Rma\Api\Data\ReturnRulesInterface
     */
    public function setDefaultResolution($period);

    /**
     * @return int
     */
    public function getDefaultResolution();

    /**
     * @param string $conditions
     *
     * @return \Amasty\Rma\Api\Data\ReturnRulesInterface
     */
    public function setConditionsSerialized($conditions);

    /**
     * @return string
     */
    public function getConditionsSerialized();

    /**
     * @param \Amasty\Rma\Api\Data\ReturnRulesCustomerGroupsInterface[] $groups
     *
     * @return \Amasty\Rma\Api\Data\ReturnRulesInterface
     */
    public function setCustomerGroups($groups);

    /**
     * @return \Amasty\Rma\Api\Data\ReturnRulesCustomerGroupsInterface[]
     */
    public function getCustomerGroups();

    /**
     * @param \Amasty\Rma\Api\Data\ReturnRulesWebsitesInterface[] $websites
     *
     * @return \Amasty\Rma\Api\Data\ReturnRulesInterface
     */
    public function setWebsites($websites);

    /**
     * @return \Amasty\Rma\Api\Data\ReturnRulesWebsitesInterface[]
     */
    public function getWebsites();

    /**
     * @param \Amasty\Rma\Api\Data\ReturnRulesResolutionsInterface[] $resolutions
     *
     * @return \Amasty\Rma\Api\Data\ReturnRulesInterface
     */
    public function setResolutions($resolutions);

    /**
     * @return \Amasty\Rma\Api\Data\ReturnRulesResolutionsInterface[]
     */
    public function getResolutions();
}
