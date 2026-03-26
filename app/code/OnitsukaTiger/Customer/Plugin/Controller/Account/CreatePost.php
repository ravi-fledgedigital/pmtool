<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Customer\Plugin\Controller\Account;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\UrlFactory;

class CreatePost
{
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $_request;


    private $resultRedirectFactory;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlModel;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    private $_redirect;

    /**
     * @var \OnitsukaTigerKorea\Customer\Helper\Data
     */
    private $dataHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timezone;


    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\Element\Template\Context $context,
        \OnitsukaTigerKorea\Customer\Helper\Data $helperAddress,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        UrlFactory $urlFactory,
        ResultFactory $resultFactory,
        \Magento\Framework\App\Action\Context $contextAction
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->messageManager = $contextAction->getMessageManager();
        $this->_request = $contextAction->getRequest();
        $this->_storeManager = $context->getStoreManager();
        $this->dataHelper = $helperAddress;
        $this->_redirect = $contextAction->getRedirect();
        $this->urlModel = $urlFactory->create();
        $this->timezone = $timezone;
        $this->resultRedirectFactory = $contextAction->getResultRedirectFactory();
        $this->resultFactory = $resultFactory;
    }

    /**
     * @param \Magento\Customer\Controller\Account\CreatePost $subject
     * @param callable $proceed
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundExecute(\Magento\Customer\Controller\Account\CreatePost $subject,  callable $proceed)
    {
        if($this->dataHelper->isKoreanThemeEnable($this->_storeManager->getStore()->getId())) {
            if($this->_request->getParam('dob')){
                $now = explode('/',$this->timezone->date()->format('Y/m/d'));
                $customerDate = explode('/',$this->_request->getParam('dob'));
                $years = (int)$now[0] - (int)$customerDate[0];
                $month = (int)$now[1] - (int)$customerDate[1];
                $days = (int)$now[2] - (int)$customerDate[2];
                $validateYearsOld = false;
                if($years > 14) {
                    $validateYearsOld = true;
                }
                if($years == 14){
                    if($month > 0){
                        $validateYearsOld = true;
                    }
                    if($month ==0){
                        if($days >=0) {
                            $validateYearsOld = true;
                        }
                    }
                }
                if($validateYearsOld) {
                    return $proceed();
                }
                $this->messageManager->addError(__('You must be at least 14 years of age at the time of registration.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                $defaultUrl = $this->urlModel->getUrl('*/*/create', ['_secure' => true]);
                return $resultRedirect->setUrl($this->_redirect->error($defaultUrl));
            }
            return $proceed();
        }else{
            return $proceed();
        }
    }
    /**
     * @param $storeId
     * @return mixed
     */
    public function formatDateOfDob($storeId)
    {
        return $this->scopeConfig->getValue('date_time/general/dob_date_format', ScopeInterface::SCOPE_STORE, $storeId);
    }
}
