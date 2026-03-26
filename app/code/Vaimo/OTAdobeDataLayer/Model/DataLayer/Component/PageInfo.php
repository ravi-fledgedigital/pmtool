<?php

namespace Vaimo\OTAdobeDataLayer\Model\DataLayer\Component;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use Vaimo\AepEventStreaming\Service\CustomerId;
use Vaimo\OTAdobeDataLayer\Api\ConfigInterface;
use Vaimo\OTAdobeDataLayer\Helper\Data;

class PageInfo implements ComponentInterface
{
    /**
     * Get country path
     */
    const COUNTRY_CODE_PATH = 'general/country/default';

    private SerializerInterface $serializer;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Vaimo\OTAdobeDataLayer\Model\Config
     */
    private $vaimoConfig;

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Vaimo\OTAdobeDataLayer\Helper\Data
     */
    protected $dataLayerHelper;

    public function __construct(
        SerializerInterface $serializer,
        ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Vaimo\OTAdobeDataLayer\Model\Config $vaimoConfig,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\Webapi\Rest\Request $request,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        Data $dataLayerHelper
    ) {
        $this->serializer = $serializer;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->vaimoConfig = $vaimoConfig;
        $this->appState = $appState;
        $this->request = $request;
        $this->categoryFactory = $categoryFactory;
        $this->dataLayerHelper = $dataLayerHelper;
    }

    public function getComponentData(): string
    {
        $siteEnvironment = $this->appState->getMode();

        if($this->appState->getMode() == "production"){
            $siteEnvironment = 'prod';
        }
        $pageInfo = [
            "pageInfo" => [
                "pageType" => $this->getPageType(),
                "brand" => 'OT',
                "country" => $this->getDefaultCountry(),
                "region" => $this->vaimoConfig->getLoggedInRegion(),
                "language" => $this->dataLayerHelper->getStoreLanguage(),
                "siteName" => $this->vaimoConfig->getLoggedInSite(),
                "siteEnvironment" => 'prod'
            ],
            "category" => $this->getCategoryInfoById()
        ];

        return $this->serializer->serialize($pageInfo);
    }

    private function getDefaultCountry()
    {
        return $this->scopeConfig->getValue(
            self::COUNTRY_CODE_PATH,
            ScopeInterface::SCOPE_WEBSITES
        );
    }

    private function getStoreCode()
    {
        return $this->storeManager->getStore()->getCode();
    }
  
    private function getPageType()
    {
        $params = $this->request->getBodyParams();

        if(isset($params['requestedComponents']) && !empty($params['requestedComponents']) && isset($params['requestedComponents']['pageType']) && !empty($params['requestedComponents']['pageType'])) {
            return $params['requestedComponents']['pageType'];
        }

        return 'Homepage';
    }

    private function getCategoryInfoById()
    {
        $params = $this->request->getBodyParams();

        if(isset($params['requestedComponents']) && !empty($params['requestedComponents']) && isset($params['requestedComponents']['categoryInfo']) && !empty($params['requestedComponents']['categoryInfo'])) {
            $categoryId = $params['requestedComponents']['categoryInfo'];

            $category = $this->categoryFactory->create()->load($categoryId);

            $collection = $category->getResourceCollection();
            $pathIds = $category->getPathIds();
            $collection->addAttributeToSelect('name');
            $collection->addAttributeToSelect('url_key');
            $collection->addAttributeToFilter('entity_id', array('in' => $pathIds));
            $collection->addAttributeToFilter('level', array('nin' => ['0', '1']));
            $catName = [];
            foreach ($collection as $cat) {
                $catName[] = $cat->getUrlKey();
            }

            $siteSection = $siteSubSection1 = $siteSubSection2 = $siteSubSection3 = '';
            if(!empty($catName)) {

                if(isset($catName[0]) && !empty($catName[0])) {
                    $siteSection = $catName[0];
                }

                if(isset($catName[1]) && !empty($catName[1])) {
                    $siteSubSection1 = $catName[1];
                }

                if(isset($catName[2]) && !empty($catName[2])) {
                    $siteSubSection2 = $catName[2];
                }

                if(isset($catName[3]) && !empty($catName[3])) {
                    $siteSubSection3 = $catName[3];
                }
            }

            $customerDashboardPageArray = [
                'customer_account_index',
                'customer_account_edit',
                'sales_order_history',
                'customer_address_index',
                'wishlist_index_index',
                'amasty_rma_account_history'
            ];

            if(isset($params['requestedComponents']['currentPage'])
                && !empty($params['requestedComponents']['currentPage'])
                && in_array($params['requestedComponents']['currentPage'],$customerDashboardPageArray)) {

                $actionNameArr = explode('_', $params['requestedComponents']['currentPage']);
                if(!empty($actionNameArr)){
                    foreach ($actionNameArr as $key => $actionValue) {
                        if($key == 0){
                            $returnData["siteSection"] =  $actionValue;
                        }else{
                            $returnData["siteSection".$key] =  $actionValue;
                        }
                    }
                }
            }else{
                $returnData = [
                    "siteSection" => $siteSection,
                    "siteSubSection1" => $siteSubSection1,
                    "siteSubSection2" => $siteSubSection2,
                    "siteSubSection3" => $siteSubSection3
                ];
            }

            return $returnData;
        }
    }
}
