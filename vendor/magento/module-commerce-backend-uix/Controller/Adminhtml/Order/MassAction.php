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

namespace Magento\CommerceBackendUix\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\CommerceBackendUix\Model\Cache\Cache;
use Magento\CommerceBackendUix\Model\MassActionNoIframeExecutor;
use Magento\CommerceBackendUix\Model\UiGridType;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

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
    private const ACTION_ID = 'actionId';
    private const ORDER_ACTION_ID = 'orderActionId';
    private const SELECTION_LIMIT = 'selectionLimit';
    private const SELECTED = 'selected';
    private const DISPLAY_IFRAME = 'displayIframe';

    /**
     * @param Context $context
     * @param Cache $cache
     * @param MassActionNoIframeExecutor $massActionExecutor
     */
    public function __construct(
        private Context $context,
        private Cache $cache,
        private MassActionNoIframeExecutor $massActionExecutor
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $actionId = $this->getRequest()->getParam(self::ORDER_ACTION_ID);
        $selectedIds = $this->getRequest()->getParam(self::SELECTED);
        $selectionLimit = $this->getSelectionLimit($actionId);
        $massAction = $this->cache->getMassAction(UiGridType::SALES_ORDER_GRID, $actionId);

        if ($selectionLimit != self::NO_LIMIT && count($selectedIds) > $selectionLimit) {
            $this->renderSelectionLimitError($selectionLimit);
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('sales/*/index');
        }

        $displayIframe =  $massAction[self::DISPLAY_IFRAME] ?? true;
        if ($displayIframe) {
            return $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        }

        $extensionId = $this->getRequest()->getParam('extensionId');
        $this->massActionExecutor->execute($massAction, $selectedIds, $extensionId, 'order');
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('sales/*/index');
    }

    /**
     * Find the limit for a specific mass action by actionId
     *
     * @param string $actionId
     * @return int
     */
    private function getSelectionLimit(string $actionId): int
    {
        $massActions = array_column(
            $this->cache->getMassActions(UiGridType::SALES_ORDER_GRID),
            self::SELECTION_LIMIT,
            self::ACTION_ID
        );
        return (int) $massActions[$actionId] ?? self::NO_LIMIT;
    }

    /**
     * Renders error if order selection limit is reached
     *
     * @param int $orderLimit
     * @return void
     */
    private function renderSelectionLimitError(int $orderLimit): void
    {
        $this->context->getMessageManager()->addErrorMessage(
            __(
                'Please select maximum %1 order(s) before executing this mass action.',
                $orderLimit
            )
        );
    }
}
