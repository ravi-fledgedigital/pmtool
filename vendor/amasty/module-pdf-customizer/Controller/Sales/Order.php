<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Controller\Sales;

class Order extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Sales\Controller\AbstractController\OrderViewAuthorizationInterface
     */
    private $orderAuthorization;

    /**
     * @var \Magento\Sales\Controller\AbstractController\OrderLoaderInterface
     */
    private $orderLoader;

    /**
     * @var \Amasty\PDFCustom\Model\Order\Pdf\Order
     */
    private $orderPdf;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    private $fileFactory;

    public function __construct(
        \Magento\Sales\Controller\AbstractController\OrderViewAuthorizationInterface $orderAuthorization,
        \Magento\Sales\Controller\AbstractController\OrderLoaderInterface $orderLoader,
        \Amasty\PDFCustom\Model\Order\Pdf\Order $orderPdf,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->orderAuthorization = $orderAuthorization;
        $this->orderLoader = $orderLoader;
        $this->orderPdf = $orderPdf;
        $this->registry = $registry;
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->orderLoader->load($this->_request);
        if ($result instanceof \Magento\Framework\Controller\ResultInterface) {
            return $result;
        }

        try {
            $order = $this->registry->registry('current_order');
            if (!$this->orderAuthorization->canView($order)) {
                return $this->getRedirect();
            }

            $filename = 'order' . $order->getIncrementId() . '.pdf';

            /** @var \Amasty\PDFCustom\Model\PdfLib\PdfInterface $pdf */
            $pdf = $this->orderPdf->getPdf([$order]);

            $content = $pdf->render();

            $response = $this->fileFactory->create(
                $filename,
                $content,
                \Magento\Framework\App\Filesystem\DirectoryList::TMP,
                'application/pdf',
                strlen($content)
            );
            // avoid double headers or double content
            return $response;
        } catch (\Exception $e) {
            return $this->getRedirect();
        }
    }

    protected function getRedirect()
    {
        return $this->_redirect('sales/order/history');
    }
}
