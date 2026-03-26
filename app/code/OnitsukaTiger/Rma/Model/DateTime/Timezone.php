<?php

namespace OnitsukaTiger\Rma\Model\DateTime;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\State;
use Magento\Backend\Model\Session;
use Magento\Framework\Stdlib\DateTime\Intl\DateFormatterFactory;

class Timezone extends \Magento\Framework\Stdlib\DateTime\Timezone
{
    const ADMIN_SALES_ORDER_VIEW_URL = "admin/sales/";
    const ADMIN_SALES_ORDER_GRID_URL = "admin/sales/order/index";
    const ADMIN_SALES_ORDER_SHIPMENT_URL = "admin/order_shipment/";
    const ADMIN_SALES_ORDER_UI_COMPONENT_URL = "admin/mui/index/render";
    const ADMIN_SALES_ORDER_COMMENT_HISTORY_URL = "admin/sales/order/commentsHistory";
    const ADMIN_TIME_ZONE_BE = "date_time/general/timezone_be";
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var State
     */
    private $state;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var Session
     */
    private $session;

    /**
     * Timezone constructor.
     * @param ScopeResolverInterface $scopeResolver
     * @param ResolverInterface $localeResolver
     * @param DateTime $dateTime
     * @param ScopeConfigInterface $scopeConfig
     * @param string $scopeType
     * @param string $defaultTimezonePath
     * @param StoreManagerInterface $storeManager
     * @param State $state
     * @param RequestInterface $request
     * @param Session $session
     */
    public function __construct(
        ScopeResolverInterface              $scopeResolver,
        ResolverInterface                   $localeResolver,
        DateTime                            $dateTime,
        ScopeConfigInterface                $scopeConfig,
        $scopeType,
        $defaultTimezonePath,
        StoreManagerInterface               $storeManager,
        State                               $state,
        RequestInterface                    $request,
        Session                             $session,
        DateFormatterFactory $dateFormatterFactory

    ) {
        parent::__construct(
            $scopeResolver,
            $localeResolver,
            $dateTime,
            $scopeConfig,
            $scopeType,
            $defaultTimezonePath,
            $dateFormatterFactory
        );
        $this->storeManager = $storeManager;
        $this->state = $state;
        $this->request = $request;
        $this->session = $session;
    }

    /**
     * @inheritdoc
     */
    public function getConfigTimezone($scopeType = null, $scopeCode = null)
    {
        if ($this->state->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE) {
            $storeId = $this->session->getStoreId();
            $saleUrl = strpos($this->request->getPathInfo(), self::ADMIN_SALES_ORDER_VIEW_URL);
            $saleIndexUrl = strpos($this->request->getPathInfo(), self::ADMIN_SALES_ORDER_GRID_URL);
            $shipmentUrl = strpos($this->request->getPathInfo(), self::ADMIN_SALES_ORDER_SHIPMENT_URL);
            $saleUiComponentUrl = strpos($this->request->getPathInfo(), self::ADMIN_SALES_ORDER_UI_COMPONENT_URL);
            $saleCommentHistoryUrl = strpos($this->request->getPathInfo(), self::ADMIN_SALES_ORDER_COMMENT_HISTORY_URL);
            if ($storeId != null  && !$saleIndexUrl && ($saleUrl || $saleUiComponentUrl || $saleCommentHistoryUrl || $shipmentUrl)) {
                $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
                $timezoneBE = $this->_scopeConfig->getValue(self::ADMIN_TIME_ZONE_BE, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $websiteId);
                return $timezoneBE;
            }
        }
        $this->session->setData("store_id", null);
        return $this->_scopeConfig->getValue(
            $this->getDefaultTimezonePath(),
            $scopeType ?: $this->_scopeType,
            $scopeCode
        );
    }
}
