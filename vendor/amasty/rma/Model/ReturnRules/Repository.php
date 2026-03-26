<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\ReturnRules;

use Amasty\Rma\Api\ReturnRulesRepositoryInterface;
use Amasty\Rma\Api\Data\ReturnRulesInterface;
use Amasty\Rma\Api\Data\ReturnRulesInterfaceFactory;
use Amasty\Rma\Api\Data\ReturnRulesWebsitesInterface;
use Amasty\Rma\Api\Data\ReturnRulesWebsitesInterfaceFactory;
use Amasty\Rma\Api\Data\ReturnRulesCustomerGroupsInterface;
use Amasty\Rma\Api\Data\ReturnRulesCustomerGroupsInterfaceFactory;
use Amasty\Rma\Api\Data\ReturnRulesResolutionsInterface;
use Amasty\Rma\Api\Data\ReturnRulesResolutionsInterfaceFactory;
use Amasty\Rma\Model\OptionSource\Status;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;

class Repository implements ReturnRulesRepositoryInterface
{
    /**
     * @var ReturnRulesInterfaceFactory
     */
    private $rulesFactory;

    /**
     * @var ResourceModel\ReturnRules
     */
    private $returnRulesResource;

    /**
     * @var ResourceModel\CollectionFactory
     */
    private $rulesCollectionFactory;

    /**
     * @var ReturnRulesWebsitesInterfaceFactory
     */
    private $rulesWebsitesFactory;

    /**
     * @var ResourceModel\ReturnRulesWebsites
     */
    private $rulesWebsitesResource;

    /**
     * @var ResourceModel\ReturnRulesWebsitesCollectionFactory
     */
    private $rulesWebsitesCollectionFactory;

    /**
     * @var ReturnRulesCustomerGroupsInterfaceFactory
     */
    private $rulesCustomerGroupsFactory;

    /**
     * @var ResourceModel\ReturnRulesCustomerGroups
     */
    private $rulesCustomerGroupsResource;

    /**
     * @var ResourceModel\ReturnRulesCustomerGroupsCollectionFactory
     */
    private $rulesCustomerGroupsCollectionFactory;

    /**
     * @var ReturnRulesResolutionsInterfaceFactory
     */
    private $rulesResolutionsFactory;

    /**
     * @var ResourceModel\ReturnRulesResolutions
     */
    private $rulesResolutionsResource;

    /**
     * @var ResourceModel\ReturnRulesResolutionsCollectionFactory
     */
    private $rulesResolutionsCollectionFactory;

    /**
     * Model storage
     * @var ReturnRulesRepositoryInterface[]
     */
    private $returnRules;

    /**
     * Storage for active rules
     * @var ReturnRulesRepositoryInterface[]
     */
    private $activeRules = [];

    public function __construct(
        ReturnRulesInterfaceFactory $rulesFactory,
        ResourceModel\ReturnRules $returnRulesResource,
        ResourceModel\CollectionFactory $rulesCollectionFactory,
        ReturnRulesWebsitesInterfaceFactory $rulesWebsitesFactory,
        ResourceModel\ReturnRulesWebsites $rulesWebsitesResource,
        ResourceModel\ReturnRulesWebsitesCollectionFactory $rulesWebsitesCollectionFactory,
        ReturnRulesCustomerGroupsInterfaceFactory $rulesCustomerGroupsFactory,
        ResourceModel\ReturnRulesCustomerGroups $rulesCustomerGroupsResource,
        ResourceModel\ReturnRulesCustomerGroupsCollectionFactory $rulesCustomerGroupsCollectionFactory,
        ReturnRulesResolutionsInterfaceFactory $rulesResolutionsFactory,
        ResourceModel\ReturnRulesResolutions $rulesResolutionsResource,
        ResourceModel\ReturnRulesResolutionsCollectionFactory $rulesResolutionsCollectionFactory
    ) {
        $this->rulesFactory = $rulesFactory;
        $this->returnRulesResource = $returnRulesResource;
        $this->rulesCollectionFactory = $rulesCollectionFactory;
        $this->rulesWebsitesFactory = $rulesWebsitesFactory;
        $this->rulesWebsitesResource = $rulesWebsitesResource;
        $this->rulesWebsitesCollectionFactory = $rulesWebsitesCollectionFactory;
        $this->rulesCustomerGroupsFactory = $rulesCustomerGroupsFactory;
        $this->rulesCustomerGroupsResource = $rulesCustomerGroupsResource;
        $this->rulesCustomerGroupsCollectionFactory = $rulesCustomerGroupsCollectionFactory;
        $this->rulesResolutionsFactory = $rulesResolutionsFactory;
        $this->rulesResolutionsResource = $rulesResolutionsResource;
        $this->rulesResolutionsCollectionFactory = $rulesResolutionsCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(ReturnRulesInterface $rule)
    {
        try {
            if ($rule->getRuleId()) {
                $rule = $this->getById($rule->getId())->addData($rule->getData());
            }
            $this->returnRulesResource->save($rule);
            $this->saveWebsites($rule);
            $this->saveCustomerGroups($rule);
            $this->saveResolutions($rule);

            unset($this->returnRules[$rule->getRuleId()]);
        } catch (\Exception $e) {
            if ($rule->getRuleId()) {
                throw new CouldNotSaveException(
                    __(
                        'Unable to save rule with ID %1. Error: %2',
                        [$rule->getRuleId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotSaveException(__('Unable to save new rule. Error: %1', $e->getMessage()));
        }

        return $rule;
    }

    /**
     * @param ReturnRulesInterface $rule
     *
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function saveWebsites($rule)
    {
        /** @var ResourceModel\ReturnRulesWebsitesCollection $rulesWebsitesCollection */
        $rulesWebsitesCollection = $this->rulesWebsitesCollectionFactory->create();
        $rulesWebsitesCollection->addFieldToFilter(
            ReturnRulesWebsitesInterface::RULE_ID,
            $rule->getRuleId()
        );
        $rulesWebsitesCollection->walk('delete');

        if ($websites = $rule->getWebsites()) {
            foreach ($websites as $website) {
                $website->setRuleId($rule->getRuleId());
                $this->rulesWebsitesResource->save($website);
            }
        }
    }

    /**
     * @param ReturnRulesInterface $rule
     *
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function saveCustomerGroups($rule)
    {
        /** @var ResourceModel\ReturnRulesCustomerGroupsCollection $rulesCustomerGroupsCollection */
        $rulesCustomerGroupsCollection = $this->rulesCustomerGroupsCollectionFactory->create();
        $rulesCustomerGroupsCollection->addFieldToFilter(
            ReturnRulesWebsitesInterface::RULE_ID,
            $rule->getRuleId()
        );
        $rulesCustomerGroupsCollection->walk('delete');

        if ($groups = $rule->getCustomerGroups()) {
            foreach ($groups as $group) {
                $group->setRuleId($rule->getRuleId());
                $this->rulesCustomerGroupsResource->save($group);
            }
        }
    }

    /**
     * @param ReturnRulesInterface $rule
     *
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function saveResolutions($rule)
    {
        /** @var ResourceModel\ReturnRulesResolutionsCollection $rulesResolutionsCollection */
        $rulesResolutionsCollection = $this->rulesResolutionsCollectionFactory->create();
        $rulesResolutionsCollection->addFieldToFilter(
            ReturnRulesWebsitesInterface::RULE_ID,
            $rule->getRuleId()
        );
        $rulesResolutionsCollection->walk('delete');

        if ($resolutions = $rule->getResolutions()) {
            foreach ($resolutions as $resolution) {
                $resolution->setRuleId($rule->getRuleId());
                $this->rulesResolutionsResource->save($resolution);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getById($ruleId)
    {
        if (!isset($this->returnRules[$ruleId])) {
            /** @var ReturnRulesInterface $rule */
            $rule = $this->rulesFactory->create();
            $this->returnRulesResource->load($rule, $ruleId);

            if (!$rule->getRuleId()) {
                throw new NoSuchEntityException(__('Rule with specified ID "%1" not found.', $ruleId));
            }
            /** @var ResourceModel\ReturnRulesWebsitesCollection $rulesWebsitesCollection */
            $rulesWebsitesCollection = $this->rulesWebsitesCollectionFactory->create();
            $rulesWebsitesCollection->addFieldToFilter(
                ReturnRulesWebsitesInterface::RULE_ID,
                $rule->getRuleId()
            );
            $rule->setWebsites($rulesWebsitesCollection->getItems());

            /** @var ResourceModel\ReturnRulesCustomerGroupsCollection $rulesCustomerGroupsCollection */
            $rulesCustomerGroupsCollection = $this->rulesCustomerGroupsCollectionFactory->create();
            $rulesCustomerGroupsCollection->addFieldToFilter(
                ReturnRulesCustomerGroupsInterface::RULE_ID,
                $rule->getRuleId()
            );
            $rule->setCustomerGroups($rulesCustomerGroupsCollection->getItems());

            /** @var ResourceModel\ReturnRulesResolutionsCollection $rulesResolutionsCollection */
            $rulesResolutionsCollection = $this->rulesResolutionsCollectionFactory->create();
            $rulesResolutionsCollection->addFieldToFilter(
                ReturnRulesResolutionsInterface::RULE_ID,
                $rule->getRuleId()
            );
            $rule->setResolutions($rulesResolutionsCollection->getItems());

            $this->returnRules[$ruleId] = $rule;
        }

        return $this->returnRules[$ruleId];
    }

    /**
     * @inheritdoc
     */
    public function getActiveRules()
    {
        if (!$this->activeRules) {
            /** @var ResourceModel\Collection $collection */
            $collection = $this->rulesCollectionFactory->create();
            $collection->addFieldToFilter(ReturnRulesInterface::STATUS, Status::ENABLED)
                ->addFieldToSelect(ReturnRulesInterface::ID);
            $items = $collection->getData();

            foreach ($items as $rule) {
                $this->activeRules[] = $this->getById($rule[ReturnRulesInterface::ID]);
            }
        }

        return $this->activeRules;
    }

    /**
     * @inheritdoc
     */
    public function delete(ReturnRulesInterface $rule)
    {
        try {
            $this->returnRulesResource->delete($rule);
            unset($this->returnRules[$rule->getRuleId()]);
        } catch (\Exception $e) {
            if ($rule->getRuleId()) {
                throw new CouldNotDeleteException(
                    __(
                        'Unable to remove rule with ID %1. Error: %2',
                        [$rule->getRuleId(), $e->getMessage()]
                    )
                );
            }
            throw new CouldNotDeleteException(__('Unable to remove rule. Error: %1', $e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById($ruleId)
    {
        $rule = $this->getById($ruleId);

        return $this->delete($rule);
    }

    /**
     * @inheritdoc
     */
    public function getEmptyRuleModel()
    {
        return $this->rulesFactory->create();
    }

    /**
     * @inheritdoc
     */
    public function getEmptyRuleWebsiteModel()
    {
        return $this->rulesWebsitesFactory->create();
    }

    /**
     * @inheritdoc
     */
    public function getEmptyRuleCustomerGroupModel()
    {
        return $this->rulesCustomerGroupsFactory->create();
    }

    /**
     * @inheritdoc
     */
    public function getEmptyRuleResolutionModel()
    {
        return $this->rulesResolutionsFactory->create();
    }

    /**
     * @inheritdoc
     */
    public function getResolutionsByRuleId($ruleId)
    {
        /** @var ResourceModel\ReturnRulesResolutionsCollection $collection */
        $collection = $this->rulesResolutionsCollectionFactory->create();
        $collection->addFieldToFilter(ReturnRulesResolutionsInterface::RULE_ID, $ruleId);

        return $collection->getData();
    }

    /**
     * @inheritdoc
     */
    public function getWebsitesByRuleId($ruleId)
    {
        /** @var ResourceModel\ReturnRulesWebsitesCollection $collection */
        $collection = $this->rulesWebsitesCollectionFactory->create();
        $collection->addFieldToFilter(ReturnRulesWebsitesInterface::RULE_ID, $ruleId);

        return $collection->getData();
    }

    /**
     * @inheritdoc
     */
    public function getCustomerGroupsByRuleId($ruleId)
    {
        /** @var ResourceModel\ReturnRulesCustomerGroupsCollection $collection */
        $collection = $this->rulesCustomerGroupsCollectionFactory->create();
        $collection->addFieldToFilter(ReturnRulesCustomerGroupsInterface::RULE_ID, $ruleId);

        return $collection->getData();
    }

    /**
     * @inheritdoc
     */
    public function getRuleResolution($resolutionId, $ruleId)
    {
        /** @var ResourceModel\ReturnRulesResolutionsCollection $collection */
        $collection = $this->rulesResolutionsCollectionFactory->create();
        $collection->addFieldToFilter(ReturnRulesResolutionsInterface::RESOLUTION_ID, $resolutionId)
            ->addFieldToFilter(ReturnRulesResolutionsInterface::RULE_ID, $ruleId);

        return $collection->getFirstItem();
    }
}
