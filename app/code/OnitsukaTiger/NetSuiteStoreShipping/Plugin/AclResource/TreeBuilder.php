<?php

namespace OnitsukaTiger\NetSuiteStoreShipping\Plugin\AclResource;

use Magento\Framework\Acl\AclResource\TreeBuilder as AclTreeBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use OnitsukaTiger\NetSuiteStoreShipping\Helper\Data;
use OnitsukaTiger\NetSuiteStoreShipping\Model\GetInformationSource;
use OnitsukaTiger\NetSuiteStoreShipping\Plugin\BackendMenu\Builder;

class TreeBuilder
{
    const DEFAULT_SORT_ORDER = 10;
    private GetInformationSource $getInformationSource;
    private Data $helper;

    /**
     * @param GetInformationSource $getInformationSource
     * @param Data $helper
     */
    public function __construct(
        GetInformationSource $getInformationSource,
        Data $helper,
    ) {
        $this->getInformationSource = $getInformationSource;
        $this->helper = $helper;
    }

    /**
     * @param AclTreeBuilder $subject
     * @param $result
     * @return mixed|void
     * @throws NoSuchEntityException
     */
    public function afterBuild(AclTreeBuilder $subject, $result)
    {
        if (count($result) > 0) {
            foreach ($result as $keyChildren => $item) {
                if ($item['id'] == Builder::MODULE_NAME_SHIPPING_FROM_STORE . '::manage') {
                    $sourceInformation = $this->getInformationSource->getShippingFromStoreData();
                    $globalDataSource = $this->getInformationSource->getSourceDuplicate();
                    $result = $this->addChildAcl($sourceInformation, $keyChildren, $result, $globalDataSource);
                }
            }
            return $result;
        }
    }

    /**
     * @param $sourceInformation
     * @param $keyChildren
     * @param $result
     * @return array|void
     * @throws NoSuchEntityException
     */
    public function addChildAcl($sourceInformation, $keyChildren, $result, $globalDataSource)
    {
        if (count($globalDataSource->getData()) > 0) {
            $child = [];
            foreach ($globalDataSource as $aclGlobal) {
                $child[]  = [
                    'id' => Builder::MODULE_NAME_SHIPPING_FROM_STORE . '::' . $aclGlobal->getSourceCode() ,
                    'title' => $aclGlobal->getFrontendName(),
                    'sortOrder' => self::DEFAULT_SORT_ORDER,
                    'children' => []
                ];
            }
            $result[$keyChildren]['children'][] = [
                'id' => Builder::MODULE_NAME_SHIPPING_FROM_STORE . '::' . 'global',
                'title' => 'Global',
                'sortOrder' => self::DEFAULT_SORT_ORDER,
                'children' => $child
            ];
        }
        $dataGlobalDuplicate = $globalDataSource->getData();
        $dataSourceGlobalDuplicate = [];
        foreach ($dataGlobalDuplicate as $itemGlobal) {
            $dataSourceGlobalDuplicate[] = $itemGlobal['source_code'];
        }

        foreach ($sourceInformation as $websiteCode => $websiteData) {
            $child = [];
            foreach ($websiteData as $keySource => $source) {
                if (in_array($keySource, $dataSourceGlobalDuplicate)) {
                    continue;
                }
                $child[]  = [
                    'id' => Builder::MODULE_NAME_SHIPPING_FROM_STORE . '::' . $keySource,
                    'title' => $source,
                    'sortOrder' => self::DEFAULT_SORT_ORDER,
                    'children' => []
                ];
            }
            if (count($websiteData) > 0) {
                $result[$keyChildren]['children'][] = [
                    'id' => Builder::MODULE_NAME_SHIPPING_FROM_STORE . '::' . str_replace(' ', '_', $this->helper->getWebsiteName($websiteCode)),
                    'title' => $this->helper->getWebsiteName($websiteCode),
                    'sortOrder' => self::DEFAULT_SORT_ORDER,
                    'children' => $child
                ];
            }
        }

        return $result;
    }
}
