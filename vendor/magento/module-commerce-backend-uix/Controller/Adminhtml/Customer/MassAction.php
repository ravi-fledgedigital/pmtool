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

namespace Magento\CommerceBackendUix\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\CommerceBackendUix\Model\Cache\Cache;
use Magento\CommerceBackendUix\Model\MassActionNoIframeExecutor;
use Magento\CommerceBackendUix\Model\UiGridType;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Controller to handle customer mass actions execution
 */
class MassAction extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_CommerceBackendUix::admin';

    private const NO_LIMIT = -1;
    private const CUSTOMER_ACTION_ID = 'customerActionId';
    private const CUSTOMER_NUMBER_LIMIT = 'customerSelectLimit';
    private const DISPLAY_IFRAME = 'displayIframe';

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param Cache $cache
     * @param MassActionNoIframeExecutor $massActionExecutor
     */
    public function __construct(
        private Context $context,
        private Filter $filter,
        private CollectionFactory $collectionFactory,
        private Cache $cache,
        private MassActionNoIframeExecutor $massActionExecutor
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     *
     * @return ResultInterface
     * @throws LocalizedException
     */
    public function execute(): ResultInterface
    {
        $actionId = $this->getRequest()->getParam(self::CUSTOMER_ACTION_ID);
        $massAction = $this->cache->getMassAction(UiGridType::CUSTOMER_GRID, $actionId);
        $customersLimit = $massAction[self::CUSTOMER_NUMBER_LIMIT] ?? self::NO_LIMIT;
        $selectedIds = $this->filter->getCollection($this->collectionFactory->create())->getAllIds();

        if ($customersLimit != self::NO_LIMIT && count($selectedIds) > $customersLimit) {
            $this->renderCustomerSelectionLimitError($customersLimit);
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('customer/*/index');
        }

        $displayIframe =  $massAction[self::DISPLAY_IFRAME] ?? true;
        if ($displayIframe) {
            return $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        }

        $extensionId = $this->getRequest()->getParam('extensionId');
        $this->massActionExecutor->execute($massAction, $selectedIds, $extensionId, UiGridType::CUSTOMER_GRID);
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('customer/index/index');
    }

    /**
     * Renders error if customer selection limit is reached
     *
     * @param int $customerLimit
     * @return void
     */
    private function renderCustomerSelectionLimitError(int $customerLimit): void
    {
        $this->messageManager->addErrorMessage(
            __(
                'Please select maximum %1 customer(s) before executing this mass action.',
                $customerLimit
            )
        );
    }
}
