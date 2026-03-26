<?php
namespace OnitsukaTigerVn\Catalog\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;
    private \Magento\Customer\Model\Group $customerGroupCollection;
    private StoreManagerInterface $storeManager;

    /**
     * @param Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Customer\Model\Group $customerGroupCollection
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Customer\Model\Group $customerGroupCollection,
        StoreManagerInterface $storeManager,
    ) {
        $this->customerSession = $customerSession;
        $this->httpContext = $httpContext;
        $this->customerGroupCollection = $customerGroupCollection;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }
}
