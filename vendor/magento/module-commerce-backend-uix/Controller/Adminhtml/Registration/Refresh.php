<?php
/**
 * ADOBE CONFIDENTIAL
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
 */
declare(strict_types=1);

namespace Magento\CommerceBackendUix\Controller\Adminhtml\Registration;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\CommerceBackendUix\Model\Cache\CacheInvalidator;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Reload registrations.
 */
class Refresh extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_CommerceBackendUix::admin';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CacheInvalidator $cacheInvalidator
     */
    public function __construct(
        private Context $context,
        private JsonFactory $resultJsonFactory,
        private CacheInvalidator $cacheInvalidator
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute(): ResultInterface
    {
        $resultJson = $this->resultJsonFactory->create();
        if ($this->cacheInvalidator->invalidate()) {
            $this->messageManager->addSuccessMessage(__('Registrations refreshed successfully.'));
            $resultJson->setData([
                'status' => 200
            ]);
        } else {
            $resultJson->setData([
                'status' => 400
            ]);
        }
        return $resultJson;
    }
}
