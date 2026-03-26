<?php
/**
 * ADOBE CONFIDENTIAL
 *
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Controller\Adminhtml\Menu;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\CommerceBackendUix\Model\Cache\Cache;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class Page extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_CommerceBackendUix::admin';

    /**
     * @param Context $context
     * @param Cache $cache
     */
    public function __construct(private Context $context, private Cache $cache)
    {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $extensionId = $this->getRequest()->getParam('extensionId');
        $registrations = $this->cache->getRegistrations();
        $page->getConfig()->getTitle()->set(__($registrations['menu']['items'][$extensionId]['page']['title'] ?? ''));
        return $page;
    }
}
