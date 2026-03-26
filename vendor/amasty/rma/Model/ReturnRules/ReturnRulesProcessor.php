<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\ReturnRules;

use Amasty\Rma\Api\ResolutionRepositoryInterface;
use Amasty\Rma\Api\ReturnRulesRepositoryInterface;
use Amasty\Rma\Model\OptionSource\Status;
use Amasty\Rma\Model\Resolution\ResourceModel\CollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;

class ReturnRulesProcessor
{
    /**
     * @var ReturnRulesRepositoryInterface
     */
    private $repository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var ResolutionRepositoryInterface
     */
    private $resolutionRepository;

    /**
     * @var CollectionFactory
     */
    private $resolutionsCollectionFactory;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    public function __construct(
        ReturnRulesRepositoryInterface $repository,
        StoreManagerInterface $storeManager,
        Session $customerSession,
        ResolutionRepositoryInterface $resolutionRepository,
        CollectionFactory $resolutionsCollectionFactory,
        TimezoneInterface $timezone
    ) {
        $this->repository = $repository;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->resolutionRepository = $resolutionRepository;
        $this->resolutionsCollectionFactory = $resolutionsCollectionFactory;
        $this->timezone = $timezone;
    }

    /**
     * @param \Amasty\Rma\Api\Data\ReturnOrderInterface $returnOrder
     * @param \Amasty\Rma\Api\Data\ReturnOrderItemInterface $returnItem
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processReturn($returnOrder, $returnItem)
    {
        $productItem = $returnItem->getProductItem() ?: $returnItem->getItem();
        $rule = $this->getRuleToApply($productItem, (int)$returnOrder->getOrder()->getId());

        if (!$rule) {
            $this->applyResolutionsToReturnItem($returnItem, [], true);

            return true;
        }
        $resolutions = $this->getResolutions($rule);
        $availableResolutions = [];
        $currentTime = $this->timezone->date()->getTimestamp();
        $orderCreationTime = $this->getResolutionStartTime($returnOrder);

        foreach ($resolutions as $resolutionId => $resolution) {
            $resolutionValue = $resolution->getValue() !== null
                ? $resolution->getValue()
                : $rule->getDefaultResolution();

            if ($resolutionValue == "0") {
                continue;
            }

            if (floor(($currentTime - $orderCreationTime) / 86400) < $resolutionValue) {
                $availableResolutions[] = $resolutionId;
            }
        }

        if (!empty($availableResolutions)) {
            $this->applyResolutionsToReturnItem($returnItem, $availableResolutions);

            return true;
        } else {
            return false;
        }
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return array|false if no active rules
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getResolutionsForProduct($product)
    {
        $rule = $this->getRuleToApply($product);
        $resolutions = [];

        if ($rule) {
            $availableResolutions = [];
            $resolutionsValues = [];

            foreach ($this->getResolutions($rule) as $resolutionId => $resolution) {
                $resolutionValue = $resolution->getValue() !== null
                    ? $resolution->getValue()
                    : $rule->getDefaultResolution();

                if ($resolutionValue == "0") {
                    continue;
                }
                $availableResolutions[] = $resolutionId;
                $resolutionsValues[$resolutionId] = $resolutionValue;
            }

            if (!empty($availableResolutions)) {
                foreach ($this->getStoreResolutions($availableResolutions) as $storeResolution) {
                    if (array_key_exists($storeResolution->getResolutionId(), $resolutionsValues)) {
                        $resolutions[] = [
                            'resolution' => $storeResolution,
                            'value' => $resolutionsValues[$storeResolution->getResolutionId()]
                        ];
                    }
                }
            }
            return $resolutions;
        } else {
            return false;
        }
    }

    private function getResolutionStartTime($returnOrder)
    {
        return strtotime($returnOrder->getOrder()->getCreatedAt()); //todo: replace createdAt to order complete time
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|bool|\Magento\Sales\Api\Data\OrderItemInterface $product
     * @param int|null $orderId
     *
     * @return \Amasty\Rma\Model\ReturnRules\ReturnRules|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getRuleToApply($product, $orderId = null)
    {
        $activeRules = $this->repository->getActiveRules();
        $validRules = [];

        /** @var \Amasty\Rma\Model\ReturnRules\ReturnRules $rule */
        foreach ($activeRules as $rule) {
            if (!$this->needToApply($rule->getCustomerGroups(), $rule->getWebsites())) {
                continue;
            }
            //if product deleted and conditions empty we can validate order
            if (!$product) {
                $validate = empty($rule->getConditions()->getConditions());
            } else {
                $product->setOrderId($orderId); //order ID needed for sale condition validation
                $validate = $rule->getConditions()->validate($product);
            }

            if ($validate) {
                $validRules[] = ['priority' => $rule->getPriority(), 'rule' => $rule];
            }
        }

        $priority = null;
        $ruleToApply = null;

        foreach ($validRules as $rule) {
            if ($priority === null || $priority > $rule['priority']) {
                $priority = $rule['priority'];
                $ruleToApply = $rule['rule'];
            }
        }

        return $ruleToApply;
    }
    /**
     * @param array $customerGroups
     * @param array $websites
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function needToApply($customerGroups, $websites)
    {
        $currentWebsite = $this->storeManager->getStore()->getWebsiteId();
        $currentGroup = $this->customerSession->getCustomerGroupId();
        $groupMatch = false;
        $websiteMatch = false;

        if (!empty($customerGroups)) {
            /** @var \Amasty\Rma\Api\Data\ReturnRulesCustomerGroupsInterface $group */
            foreach ($customerGroups as $group) {
                if ($group->getCustomerGroupId() == $currentGroup) {
                    $groupMatch = true;
                    break;
                }
            }
        } else {
            $groupMatch = true;
        }

        if (!empty($websites)) {
            /** @var \Amasty\Rma\Api\Data\ReturnRulesWebsitesInterface $website */
            foreach ($websites as $website) {
                if ($website->getWebsiteId() == $currentWebsite) {
                    $websiteMatch = true;
                    break;
                }
            }
        } else {
            $websiteMatch = true;
        }

        return $groupMatch && $websiteMatch;
    }

    /**
     * @param \Amasty\Rma\Api\Data\ReturnOrderItemInterface $returnItem
     * @param array $availableResolutions
     * @param bool $applyAll
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function applyResolutionsToReturnItem($returnItem, $availableResolutions = [], $applyAll = false)
    {
        if ($applyAll) {
            $returnItem->setResolutions($this->resolutionRepository
                ->getResolutionsByStoreId($this->storeManager->getStore()->getId()));
        } else {
            $returnItem->setResolutions($this->getStoreResolutions($availableResolutions));
        }
    }

    /**
     * @param array $availableResolutions
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getStoreResolutions($availableResolutions)
    {
        $resolutionsToApply = [];
        $storeResolutions = $this->resolutionRepository
            ->getResolutionsByStoreId($this->storeManager->getStore()->getId());

        foreach ($storeResolutions as $resolution) {
            if (in_array($resolution->getResolutionId(), $availableResolutions)) {
                $resolutionsToApply[$resolution->getResolutionId()] = $resolution;
            }
        }

        return $resolutionsToApply;
    }
    /**
     * @param \Amasty\Rma\Model\ReturnRules\ReturnRules $rule
     *
     * @return array
     */
    private function getResolutions($rule)
    {
        $allResolutions = $this->resolutionsCollectionFactory->create()->getItems();
        $result = [];

        /** @var \Amasty\Rma\Model\Resolution\Resolution $resolution */
        foreach ($allResolutions as $resolution) {
            if ($resolution->getStatus() == Status::DISABLED) {
                continue;
            }
            $result[$resolution->getResolutionId()] = $this->repository->getRuleResolution(
                $resolution->getResolutionId(),
                $rule->getRuleId()
            );
        }

        return $result;
    }
}
