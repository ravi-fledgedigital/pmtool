<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\ReturnRules;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Rule\Model\AbstractModel;
use Amasty\Rma\Api\Data\ReturnRulesInterface;

class ReturnRules extends AbstractModel implements ReturnRulesInterface
{
    /**#@+
     * Constants
     */
    public const CURRENT_RETURN_RULE = 'current_amrma_returnrule';
    public const FORM_NAMESPACE = 'amrma_returnrules_form';
    /**#@-*/

    /**
     * @var string
     */
    protected $_eventPrefix = 'return_rule';

    /**
     * @var string
     */
    protected $_eventObject = 'rule';

    /**
     * @var Condition\CombineFactory
     */
    protected $combineFactory;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Amasty\Rma\Model\ReturnRules\Condition\CombineFactory $combineFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->combineFactory = $combineFactory;
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $localeDate,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Model Init
     *
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(\Amasty\Rma\Model\ReturnRules\ResourceModel\ReturnRules::class);
        $this->setIdFieldName(ReturnRulesInterface::ID);
    }

    public function getConditionsInstance()
    {
        return $this->combineFactory->create();
    }

    public function getActionsInstance()
    {
        return $this->combineFactory->create();
    }

    /**
     * @inheritdoc
     */
    public function setRuleId($id)
    {
        return $this->setData(ReturnRulesInterface::ID, (int)$id);
    }

    /**
     * @inheritdoc
     */
    public function getRuleId()
    {
        return (int)$this->_getData(ReturnRulesInterface::ID);
    }

    /**
     * @inheritdoc
     */
    public function setName($name)
    {
        return $this->setData(ReturnRulesInterface::NAME, $name);
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->_getData(ReturnRulesInterface::NAME);
    }

    /**
     * @inheritdoc
     */
    public function setStatus($status)
    {
        return $this->setData(ReturnRulesInterface::STATUS, (int)$status);
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return (int)$this->_getData(ReturnRulesInterface::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setPriority($priority)
    {
        return $this->setData(ReturnRulesInterface::PRIORITY, (int)$priority);
    }

    /**
     * @inheritdoc
     */
    public function getPriority()
    {
        return (int)$this->_getData(ReturnRulesInterface::PRIORITY);
    }

    /**
     * @inheritdoc
     */
    public function setDefaultResolution($period)
    {
        return $this->setData(ReturnRulesInterface::DEFAULT_RESOLUTION, (int)$period);
    }

    /**
     * @inheritdoc
     */
    public function getDefaultResolution()
    {
        return (int)$this->_getData(ReturnRulesInterface::DEFAULT_RESOLUTION);
    }

    /**
     * @inheritdoc
     */
    public function setConditionsSerialized($conditions)
    {
        return $this->setData(ReturnRulesInterface::CONDITIONS_SERIALIZED, $conditions);
    }

    /**
     * @inheritdoc
     */
    public function getConditionsSerialized()
    {
        return $this->_getData(ReturnRulesInterface::CONDITIONS_SERIALIZED);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerGroups($groups)
    {
        return $this->setData(ReturnRulesInterface::CUSTOMER_GROUPS, $groups);
    }

    /**
     * @inheritdoc
     */
    public function getCustomerGroups()
    {
        return $this->_getData(ReturnRulesInterface::CUSTOMER_GROUPS);
    }

    /**
     * @inheritdoc
     */
    public function setWebsites($websites)
    {
        return $this->setData(ReturnRulesInterface::WEBSITES, $websites);
    }

    /**
     * @inheritdoc
     */
    public function getWebsites()
    {
        return $this->_getData(ReturnRulesInterface::WEBSITES);
    }

    /**
     * @inheritdoc
     */
    public function setResolutions($resolutions)
    {
        return $this->setData(ReturnRulesInterface::RESOLUTIONS, $resolutions);
    }

    /**
     * @inheritdoc
     */
    public function getResolutions()
    {
        return $this->_getData(ReturnRulesInterface::RESOLUTIONS);
    }
}
