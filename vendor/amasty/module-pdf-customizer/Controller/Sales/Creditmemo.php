<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Controller\Sales;

class Creditmemo extends \Magento\Framework\App\Action\Action
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
     * @var \Magento\Sales\Api\CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    /**
     * @var \Amasty\PDFCustom\Model\Order\Pdf\Creditmemo
     */
    private $creditmemoPdf;

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
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository,
        \Amasty\PDFCustom\Model\Order\Pdf\Creditmemo $creditmemoPdf,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->orderAuthorization = $orderAuthorization;
        $this->orderLoader = $orderLoader;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->creditmemoPdf = $creditmemoPdf;
        $this->registry = $registry;
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $creditmemoId = (int)$this->getRequest()->getParam('creditmemo_id');
        if ($creditmemoId) {
            /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
            $creditmemo = $this->creditmemoRepository->get($creditmemoId);
            $order = $creditmemo->getOrder();
            $this->registry->register('current_order', $order);
        } else {
            $result = $this->orderLoader->load($this->_request);
            if ($result instanceof \Magento\Framework\Controller\ResultInterface) {
                return $result;
            }
            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->registry->registry('current_order');
        }

        try {
            if (!$this->orderAuthorization->canView($order)) {
                return $this->getRedirect();
            }

            /** @var \Amasty\PDFCustom\Model\PdfLib\PdfInterface $pdf */
            if (isset($creditmemo)) {
                $filename = 'creditmemo' . $creditmemo->getIncrementId() . '.pdf';
                $pdf = $this->creditmemoPdf->getPdf([$creditmemo]);
            } else {
                $filename = 'creditmemos_of_order' . $order->getIncrementId() . '.pdf';
                $pdf = $this->creditmemoPdf->getPdf($order->getCreditmemosCollection());
            }

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
