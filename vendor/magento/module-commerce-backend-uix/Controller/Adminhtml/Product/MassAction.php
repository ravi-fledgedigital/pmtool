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

namespace Magento\CommerceBackendUix\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CommerceBackendUix\Model\Cache\Cache;
use Magento\CommerceBackendUix\Model\MassActionNoIframeExecutor;
use Magento\CommerceBackendUix\Model\UiGridType;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Controller to display apps mass actions
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
    private const PRODUCT_ACTION_ID = 'productActionId';
    private const PRODUCT_NUMBER_LIMIT = 'productSelectLimit';
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
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $actionId = $this->getRequest()->getParam(self::PRODUCT_ACTION_ID);
        $massAction = $this->cache->getMassAction(UiGridType::PRODUCT_LISTING_GRID, $actionId);
        $productsLimit = $massAction[self::PRODUCT_NUMBER_LIMIT] ?? self::NO_LIMIT;
        $selectedIds = $collection->getAllIds();

        if ($productsLimit != self::NO_LIMIT && count($selectedIds) > $productsLimit) {
            $this->renderProductSelectionLimitError($productsLimit);
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('catalog/*/index');
        }

        $displayIframe =  $massAction[self::DISPLAY_IFRAME] ?? true;
        if ($displayIframe) {
            return $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        }

        $extensionId = $this->getRequest()->getParam('extensionId');
        $this->massActionExecutor->execute($massAction, $selectedIds, $extensionId, 'product');
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('catalog/*/index');
    }

    /**
     * Renders error if product selection limit is reached
     *
     * @param int $productsLimit
     * @return void
     */
    private function renderProductSelectionLimitError(int $productsLimit): void
    {
        $this->messageManager->addErrorMessage(
            __(
                'Please select maximum %1 product(s) before executing this mass action.',
                $productsLimit
            )
        );
    }
}
