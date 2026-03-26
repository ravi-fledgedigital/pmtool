<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Amasty\Rolepermissions\Plugin\Framework\Data\Collection;

use Amasty\Rolepermissions\Helper\Data;
use Magento\Framework\DB\Select;
use Magento\Sales\Model\ResourceModel\Order\Customer\Collection as OrderCustomerCollection;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\Collection as PaymentTransactionCollection;

class AbstractDb
{
    /**
     * @var \Amasty\Rolepermissions\Helper\Data\Proxy $helper
     */
    private $helper;

    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    public function beforeGetSize($subject)
    {
        if ($subject instanceof \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection) {
            $restrictedAttributeIds = $this->helper->getRestrictedAttributeIds();

            if ($restrictedAttributeIds) {
                $restrictedSetIds = $this->helper->getRestrictedSetIds();
                $subject->addFieldToFilter('attribute_set_id', ['nin' => $restrictedSetIds]);
            }

            return;
        }

        $rule = $this->helper->currentRule();
        if ($rule && $rule->getScopeStoreviews()) {
            if ($subject instanceof OrderCustomerCollection) {
                $subject->addAttributeToFilter(
                    'store_id',
                    ['in' => $rule->getScopeStoreviews()]
                );
            } elseif ($subject instanceof PaymentTransactionCollection) {
                $fromPart = $subject->getSelect()->getPart(Select::FROM);
                if (isset($fromPart['so'])) {
                    $subject->addAttributeToFilter(
                        'so.store_id',
                        ['in' => $rule->getScopeStoreviews()]
                    );
                }
            }
        }
    }

    public function afterGetData($subject, $result)
    {
        if ($subject instanceof \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection) {
            if (empty($result)) {
                $result[] = ['value' => '', 'label' => ''];
            }
        }

        return $result;
    }

    public function beforeGetData($subject)
    {
        if ($subject instanceof \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection) {
            $restrictedAttributeIds = $this->helper->getRestrictedAttributeIds();

            if ($restrictedAttributeIds) {
                $restrictedSetIds = $this->helper->getRestrictedSetIds();
                $subject->addFieldToFilter('attribute_set_id', ['nin' => $restrictedSetIds]);
            }
        }
    }
}
