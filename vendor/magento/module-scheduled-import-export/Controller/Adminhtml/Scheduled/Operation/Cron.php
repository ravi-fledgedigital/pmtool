<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScheduledImportExport\Controller\Adminhtml\Scheduled\Operation;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Area;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DataObject;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\Import\RenderErrorMessages;
use Magento\ScheduledImportExport\Controller\Adminhtml\Scheduled\Operation as OperationController;
use Magento\ScheduledImportExport\Model\Scheduled\Operation;
use Magento\ImportExport\Model\History as ModelHistory;

class Cron extends OperationController implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * Run task through http request.
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $result = false;
        try {
            $operationId = (int)$this->getRequest()->getParam('operation');
            $schedule = new DataObject();
            $schedule->setJobCode(
                Operation::CRON_JOB_NAME_PREFIX . $operationId
            );

            /*
               We need to set default (frontend) area to send email correctly because we run cron task from backend.
               If it wouldn't be done, then in email template resources will be loaded from adminhtml area
               (in which we have only default theme) which is defined in preDispatch()

               Add: After elimination of skins and refactoring of themes we can't just switch area,
               cause we can't be sure that theme set for previous area exists in new one
            */
            /** @var \Magento\Framework\View\DesignInterface $design */
            $design = $this->_objectManager->get(\Magento\Framework\View\DesignInterface::class);
            $area = $design->getArea();
            $theme = $design->getDesignTheme();
            $design->setDesignTheme(
                $design->getConfigurationDesignTheme(Area::AREA_FRONTEND),
                Area::AREA_FRONTEND
            );
            /** @var \Magento\ScheduledImportExport\Model\Observer $result */
            $result = $this->_objectManager->get(\Magento\ScheduledImportExport\Model\Observer::class)
                ->processScheduledOperation($schedule, true);
            // restore current design area and theme
            $design->setDesignTheme($theme, $area);
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }

        $errorAggregator = $this->_objectManager->get(ProcessingErrorAggregatorInterface::class);
        $errorAggregatorCount = $errorAggregator->getErrorsCount();
        if ($result || $errorAggregatorCount) {
            $this->messageManager->addSuccess(__('The operation ran.'));
            if ($errorAggregator->getErrorsCount()) {
                $renderErrorMessages = $this->_objectManager->get(RenderErrorMessages::class);
                $historyModel = $this->_objectManager->get(ModelHistory::class);
                $noticeHtml = $historyModel->getSummary();

                if ($historyModel->getErrorFile()) {
                    $noticeHtml .=  '<div class="import-error-wrapper">' . __('Only the first 100 errors are shown. ')
                        . '<a href="'
                        . $renderErrorMessages->createDownloadUrlImportHistoryFile($historyModel->getErrorFile())
                        . '">' . __('Download full report') . '</a></div>';
                }
                $this->messageManager->addNotice(
                    $noticeHtml
                );
                try {
                    $this->messageManager->addNotice(
                        $renderErrorMessages->renderMessages($errorAggregator)
                    );
                } catch (\Exception $e) {
                    foreach ($renderErrorMessages->getErrorMessages($errorAggregator) as $errorMessage) {
                        $this->messageManager->addError($errorMessage);
                    }
                }
            }
        } else {
            $this->messageManager->addError(__('We can\'t run the operation right now, see error log for details.'));
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('adminhtml/*/index');
        return $resultRedirect;
    }
}
