<?php

namespace OnitsukaTigerIndo\RmaAccount\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Construct Method
     *
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->_storeManager = $storeManager;
    }

    /**
     * Get Frontend Url
     *
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getFrontendUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }
}
