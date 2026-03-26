<?php
declare(strict_types=1);

namespace OnitsukaTiger\NetsuiteReturnOrderSync\Plugin\Account;

use Amasty\Rma\Controller\Account\Save;
use Amasty\Rma\Model\ConfigProvider;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use OnitsukaTiger\NetsuiteReturnOrderSync\Helper\Data;

class SavePlugin {

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var Data
     */
    protected $rmaHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param RedirectFactory $resultRedirectFactory
     * @param ConfigProvider $configProvider
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param Data $rmaHelper
     */
    public function __construct(
        RedirectFactory $resultRedirectFactory,
        ConfigProvider $configProvider,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Data $rmaHelper
    ){
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->configProvider = $configProvider;
        $this->storeManager = $storeManager;
        $this->rmaHelper = $rmaHelper;
    }

    /**
     * @param Save $subject
     * @param $result
     *
     * @return Redirect|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterExecute(Save $subject, $result){
        $resultRedirect = $this->resultRedirectFactory->create();
        $rmaAlgorithmEnabled = $this->rmaHelper->getRmaAlgorithmConfig('enabled', $this->storeManager->getStore()->getId());
        if($rmaAlgorithmEnabled) {
            $resultRedirect->setPath($this->configProvider->getUrlPrefix(). '/account/history');
            return $resultRedirect;
        }
        return $result;

    }
}
