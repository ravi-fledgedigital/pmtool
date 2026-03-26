<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Rules
 */


namespace OnitsukaTiger\Rules\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Amasty\Rules\Model\DiscountBreakdownLineFactory;

/**
 * Collect and store discount data for each rule or quote item
 */
class DiscountRegistry extends \Amasty\Rules\Model\DiscountRegistry
{
    /**#@+
     * Keys for DataPersistor.
     */
    const DISCOUNT_REGISTRY_DATA = 'amasty_rules_discount_registry_data';

    const DISCOUNT_REGISTRY_SHIPPING_DATA = 'amasty_rules_discount_registry_shipping_data';
    /**#@-*/

    /**
     * @var \Magento\SalesRule\Api\RuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var DiscountBreakdownLineFactory
     */
    private $breakdownLineFactory;

    /**
     * @var \Magento\Framework\App\Request\DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;


    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepository,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor,
        DiscountBreakdownLineFactory $breakdownLineFactory
    ) {
        $this->storeManager = $storeManager;
        $this->ruleRepository = $ruleRepository;
        $this->logger = $logger;
        $this->breakdownLineFactory = $breakdownLineFactory;
        $this->dataPersistor = $dataPersistor;
        parent::__construct($storeManager, $ruleRepository, $logger,$dataPersistor,$breakdownLineFactory);
    }

    /**
     * Return amount of discount for each rule
     *
     * @return \Amasty\Rules\Api\Data\DiscountBreakdownLineInterface[]
     */
    public function getRulesWithAmount()
    {
        $totalAmount = [];
        $shippingDiscountDataForBreakdown = $this->getShippingDiscountDataForBreakdown();

        try {
            foreach ($this->getDiscount() as $ruleId => $ruleItemsAmount) {
                /** @var \Magento\SalesRule\Api\Data\RuleInterface $rule */
                $rule = $this->ruleRepository->getById($ruleId);
                $ruleAmount = array_sum($ruleItemsAmount);

                if (isset($shippingDiscountDataForBreakdown[$ruleId])) {
                    $ruleAmount += $shippingDiscountDataForBreakdown[$ruleId];
                }

                if ($ruleAmount > 0) {
                    $breakdownLine = $this->breakdownLineFactory->create();

                    if ($this->getRuleStoreLabel($rule)) {
                        $breakdownLine->setRuleName($this->getRuleStoreLabel($rule));
                    } else {
                        $breakdownLine->setRuleName($rule->getName());
                    }
                    $breakdownLine->setRuleAmount($ruleAmount);

                    $totalAmount[] = $breakdownLine;
                }
            }
        } catch (NoSuchEntityException $entityException) {
            $this->logger->critical($entityException);
        } catch (LocalizedException $e) {
            $this->logger->critical($e);
        }

        return $totalAmount;
    }

    /**
     * @param \Magento\SalesRule\Api\Data\RuleInterface $rule
     *
     * @return null|string
     *
     * @throws NoSuchEntityException
     */
    private function getRuleStoreLabel($rule)
    {
        $storeId = $this->storeManager->getStore()->getId();
        $storeLabel = $storeLabelDefault = null;

        /* @var $label \Magento\SalesRule\Model\Data\RuleLabel */
        foreach ($rule->getStoreLabels() as $label) {
            if ($label->getStoreId() === 0) {
                $storeLabelDefault = $label->getStoreLabel();
            }

            if ($label->getStoreId() == $storeId) {
                $storeLabel = $label->getStoreLabel();
                break;
            }
        }

        $storeLabel = $storeLabel ?: $storeLabelDefault;

        return $storeLabel;
    }
}
