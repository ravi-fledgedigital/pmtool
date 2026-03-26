<?php
declare(strict_types=1);
namespace OnitsukaTigerKorea\Sales\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\SalesRule\Model\RuleFactory;
use Magento\Framework\Serialize\SerializerInterface;

class SaveCouponCondition implements  ObserverInterface {

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * SaveCouponCondition constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param RuleFactory $ruleFactory
     * @param SerializerInterface $serializer
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        RuleFactory $ruleFactory,
        SerializerInterface $serializer
    ) {
        $this->orderRepository = $orderRepository;
        $this->ruleFactory = $ruleFactory;
        $this->serializer = $serializer;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /**
         * @var OrderInterface $order
         */
        $order = $observer->getEvent()->getOrder();
        if (!$order || !$order->getAppliedRuleIds()) {
            return ;
        }

        $ruleIds = $this->getRuleIds($order);
        if($ruleIds){
            $data = array();
            foreach ($ruleIds as $ruleId) {
                if (!$ruleId) {
                    continue;
                }
                $this->saveCouponCondition($order, $ruleId);
            }

            $this->orderRepository->save($order);
        }
    }

    /**
     * @param OrderInterface $order
     * @param $ruleId
     */
    private function saveCouponCondition(OrderInterface $order, $ruleId)
    {
        $rule = $this->ruleFactory->create()->load($ruleId);
        if($order->getData('coupon_condition_serialized_rule')) {
            $data = $this->serializer->unserialize($order->getData('coupon_condition_serialized_rule'));

        }
        $data[$ruleId] = [
            'condition' =>  $rule->getConditionsSerialized(),
            'action' =>  $rule->getActionsSerialized()
        ];
        $order->setData('coupon_condition_serialized_rule', $this->serializer->serialize($data));
    }

    /**
     * @param $order
     *
     * @return array
     */
    private function getRuleIds($order): array
    {
        $ruleIds = explode(',', $order->getAppliedRuleIds());
        return array_unique($ruleIds);
    }
}
