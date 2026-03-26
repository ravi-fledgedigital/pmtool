<?php

namespace OnitsukaTigerCpss\Crm\Plugin\Model;

use Cpss\Crm\Helper\Customer;
use Cpss\Crm\Model\PointConfigProvider as CrmPointConfigProvider;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use OnitsukaTigerCpss\Crm\Helper\HelperData as CpssHelperData;

/**
 * Point Config Provider
 */
class PointConfigProvider
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Customer
     */
    protected $customerHelper;

    /**
     * @var CpssHelperData
     */
    protected $cpssHelperData;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Customer $customerHelper,
        CpssHelperData $cpssHelperData
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->customerHelper = $customerHelper;
        $this->cpssHelperData = $cpssHelperData;
    }

    /**
     * Add minimum points and order to config
     *
     * @param CrmPointConfigProvider $subject
     * @param array $result
     * @return array
     * @throws Exception
     */
    public function afterGetConfig(CrmPointConfigProvider $subject, array $result)
    {
        if ($this->customerHelper->isModuleEnabled()) {
            $minimumPoints = $this->cpssHelperData->getMinimumPoints();
            $minimumOrder = $this->cpssHelperData->getMinimumOrder();
            $pointMultiplyBy = $this->cpssHelperData->getPointMultiplyBy();
            if (empty($minimumPoints)) {
                $minimumPoints = 0;
            }
            $result['minimumPoints'] = $minimumPoints;
            $result['pointMultiplyBy'] = $pointMultiplyBy;
            $result['isActiveMinimumOrder'] = $minimumOrder['isActiveMinimumOrder'];
            $result['minimumOrderValue'] = $minimumOrder['minimumOrderValue'];
            $result['minimumOrderMessage'] = $minimumOrder['minimumOrderMessage'];
            $result['isValidMinimumOrder'] = $minimumOrder['isValidMinimumOrder'];
        }
        return $result;
    }
}
