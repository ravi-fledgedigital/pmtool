<?php

namespace OnitsukaTiger\NetSuiteStoreShipping\Model;

use Magento\Store\Model\StoreManagerInterface;

class StoreSEA
{
    private StoreManagerInterface $storeManager;

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * @param $storeId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isScopeSEA($storeId)
    {
        $seaScopeCode = [
            'web_sg_en',
            'web_my_en',
            'web_th_en',
            'web_th_th',
            'web_vn_vi',
            'web_vn_en'
        ];
        $storeCode = $this->storeManager->getStore($storeId)->getCode();
        if (in_array($storeCode, $seaScopeCode)) {
            return true;
        }
        return false;
    }

}
