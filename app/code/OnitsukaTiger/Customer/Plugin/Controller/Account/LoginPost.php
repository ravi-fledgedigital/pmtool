<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Customer\Plugin\Controller\Account;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\UrlInterface;

class LoginPost
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

    protected $_redirect;

    protected $urlInterface;


    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\Element\Template\Context   $context,
        ResultFactory                                      $resultFactory,
        \Magento\Framework\App\Action\Context              $contextAction,
        RedirectInterface                                  $_redirect,
        UrlInterface                                       $urlInterface
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->messageManager = $contextAction->getMessageManager();
        $this->_storeManager = $context->getStoreManager();
        $this->resultFactory = $resultFactory;
        $this->_redirect = $_redirect;
        $this->urlInterface = $urlInterface;
    }

    /**
     * @param \Magento\Customer\Controller\Account\LoginPost $subject
     * @param $result
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function afterExecute(\Magento\Customer\Controller\Account\LoginPost $subject, $result)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/logger_file.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $refererUrl = $this->_redirect->getRefererUrl();
        $parsedUrl = parse_url($refererUrl);
        $parameters = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $parameters);
        }
        $isRedirect = false;

        if (isset($parameters['cusRedirectionUrl']) &&
            isset($parameters['autoAdd']) &&
            isset($parameters['productId']) &&
            !empty($parameters['cusRedirectionUrl']) &&
            !empty($parameters['autoAdd']) &&
            !empty($parameters['productId'])
        ) {
            $isRedirect = true;
        }

        $configTopPage = $this->scopeConfig->getValue(
            'customer/startup/redirect_hompage',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $error = 0;
        if ($errorMessage = $this->messageManager->getMessages()->getErrors()) {
            $error = 1;
        }
        if (!$error) {
            if ($configTopPage) {
                $redirectUrl = $this->_storeManager->getStore()->getBaseUrl();
                $subject->getResponse()->setRedirect($redirectUrl);
                $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $result->setUrl($redirectUrl);
                $logger->info("Redirect URL : " . $redirectUrl);
            }

        }
        if ($isRedirect) {
            $url = $this->urlInterface->getUrl('restock/add/stock') . "&autoAdd=" . base64_decode($parameters['autoAdd']) . "&productId=" . base64_decode($parameters['productId']);
            $subject->getResponse()->setRedirect($url);
            $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $result->setUrl($url);
        }
        return $result;
    }
}