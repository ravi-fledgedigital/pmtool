<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTiger\Store\Plugin\Controller\Store;


/**
 * Handles store switching url and makes redirect.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Redirect
{

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Magento\Framework\Encryption\UrlCoder
     */
    private $_urlCoder;


    /**
     * Redirect constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Encryption\UrlCoder $urlCoder
     * @param \Magento\Framework\App\Action\Context $contextAction
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Encryption\UrlCoder $urlCoder,
        \Magento\Framework\App\Action\Context $contextAction
    ) {
        $this->_storeManager = $context->getStoreManager();
        $this->_urlCoder = $urlCoder;
        $this->messageManager = $contextAction->getMessageManager();
    }

    /**
     * @param \Magento\Store\Controller\Store\Redirect $subject
     * @param $result
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterExecute(\Magento\Store\Controller\Store\Redirect $subject, $result)
    {
        $url = (string)$subject->getRequest()->getParam(\Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED);
        if ($url) {
            $redirectUrl = explode('?',$this->_urlCoder->decode($url))[0];
            $this->messageManager->getMessages(true);
            $subject->getResponse()->setRedirect($redirectUrl);
        }
        return $result;
    }
}
