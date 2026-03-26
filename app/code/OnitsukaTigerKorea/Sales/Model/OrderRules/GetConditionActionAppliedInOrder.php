<?php
declare(strict_types=1);
namespace OnitsukaTigerKorea\Sales\Model\OrderRules;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class GetConditionActionAppliedInOrder
 * @package OnitsukaTigerKorea\Sales\Model\OrderRules
 */
class GetConditionActionAppliedInOrder {

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param SerializerInterface $serializer
     */
    public function __contruct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * @param OrderInterface $order
     * @param null $ruleId
     * @return false|mixed
     */
    public function execute(OrderInterface $order, $ruleId = null){
        $conditionActionRulesApplied = $this->serializer->unserialize($order->getData('coupon_condition_serialized_rule'));
        foreach($conditionActionRulesApplied as $id => $conditionActionRule){
            if($id == $ruleId) {
                return $conditionActionRule;
            }
        }
        return false;
    }
}
