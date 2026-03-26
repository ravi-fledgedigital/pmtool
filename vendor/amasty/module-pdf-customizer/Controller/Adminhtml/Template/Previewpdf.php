<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Controller\Adminhtml\Template;

use Amasty\PDFCustom\Model\ComponentChecker;
use Amasty\PDFCustom\Model\PdfFactory;
use Amasty\PDFCustom\Model\PdfLib\PdfLibProvider;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\Redirect;

class Previewpdf extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Amasty_PDFCustom::template';

    /**
     * @var PdfFactory
     */
    private $pdfFactory;

    /**
     * @var PdfLibProvider
     */
    private $pdfLibProvider;

    /**
     * @var ComponentChecker
     */
    private $componentChecker;

    public function __construct(
        Context $context,
        ?PdfFactory $pdfFactory, // @deprecated
        ComponentChecker $componentChecker,
        ?PdfLibProvider $pdfLibProvider = null // TODO move to not optional
    ) {
        $this->pdfFactory = $pdfFactory;
        $this->pdfLibProvider = $pdfLibProvider ?? ObjectManager::getInstance()->get(PdfLibProvider::class);
        parent::__construct($context);
        $this->componentChecker = $componentChecker;
    }

    /**
     * Preview transactional email action
     *
     * @return Raw|Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->componentChecker->isComponentsExist()) {
            $this->messageManager->addErrorMessage($this->componentChecker->getComponentsErrorMessage());

            return $resultRedirect->setPath('*/*/');
        }

        try {
            $html = $this->_view->getLayout()
                ->createBlock(\Amasty\PDFCustom\Block\Adminhtml\Template\Preview::class, 'preview.page.content')
                ->toHtml();

            $pdf = $this->pdfLibProvider->get();
            $pdf->setHtml($html);
            $rawPdf = $pdf->render();

            /** @var Raw $raw */
            $raw = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
            $raw->setHeader('Content-type', "application/x-pdf");
            $raw->setHeader('Content-Security-Policy', "script-src 'none'");
            $raw->setHeader('Content-Disposition', "inline; filename=preview.pdf");
            $raw->setContents($rawPdf);

            return $raw;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred. The PDF template can not be opened for preview.')
            );

            return $resultRedirect->setPath('*/*/');
        }
    }
}
