<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2024 Adobe
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
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooks\Controller\Adminhtml\Log;

use Magento\AdobeCommerceWebhooks\Api\Data\WebhookLogInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * Controller for the webhooks logs UI grid.
 */
class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_AdobeCommerceWebhooks::webhooks_log_grid';

    /**
     * @param Context $context
     * @param PageFactory $pageFactory
     */
    public function __construct(
        Context $context,
        private PageFactory $pageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Creates and returns the webhooks logs admin UI grid page.
     *
     * @return Page
     */
    public function execute(): Page
    {
        $resultPage = $this->pageFactory->create();
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $hookName = $this->getRequest()->getParam(WebhookLogInterface::FIELD_HOOK_NAME);
        $pageTitle = $hookName ? 'Hook Logs: ' . $hookName : 'Webhooks Logs';
        $resultPage->getConfig()->getTitle()->prepend(__($pageTitle));

        return $resultPage;
    }
}
