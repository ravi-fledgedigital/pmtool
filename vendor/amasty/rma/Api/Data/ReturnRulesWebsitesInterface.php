<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Api\Data;

/**
 * Interface ReturnRulesWebsitesInterface
 */
interface ReturnRulesWebsitesInterface
{
    /**#@+
     * Constants defined for keys of data array
     */
    public const RULE_WEBSITE_ID = 'rule_website_id';
    public const RULE_ID = 'rule_id';
    public const WEBSITE_ID = 'website_id';
    /**#@-*/

    /**
     * @param int $id
     *
     * @return \Amasty\Rma\Api\Data\ReturnRulesWebsitesInterface
     */
    public function setRuleWebsiteId($id);

    /**
     * @return int
     */
    public function getRuleWebsiteId();

    /**
     * @param int $id
     *
     * @return \Amasty\Rma\Api\Data\ReturnRulesWebsitesInterface
     */
    public function setRuleId($id);

    /**
     * @return int
     */
    public function getRuleId();

    /**
     * @param int $id
     *
     * @return \Amasty\Rma\Api\Data\ReturnRulesWebsitesInterface
     */
    public function setWebsiteId($id);

    /**
     * @return int
     */
    public function getWebsiteId();
}
