<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Customer\Plugin\Controller\Address;


/**
 * Handles store switching url and makes redirect.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Index
{

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $_redirect;


    /**
     * Index constructor.
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\View\Element\Template\Context $context
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\View\Element\Template\Context $context
    ) {
        $this->_customerSession = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
        $this->_storeManager = $context->getStoreManager();
    }

    /**
     * @param \Magento\Customer\Controller\Address\Index $subject
     * @param callable $proceed
     * @return \Magento\Framework\View\Result\Page
     */
    public function aroundExecute(\Magento\Customer\Controller\Address\Index $subject, callable $proceed)
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        return $resultPage;

    }

    /**
     * Retrieve customer session object
     *
     * @return \Magento\Customer\Model\Session
     */
    protected function _getSession()
    {
        return $this->_customerSession;
    }
}
