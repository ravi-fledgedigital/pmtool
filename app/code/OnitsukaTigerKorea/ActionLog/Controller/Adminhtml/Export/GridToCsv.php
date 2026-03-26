<?php
/**
 * GridToCsv
 */

namespace OnitsukaTigerKorea\ActionLog\Controller\Adminhtml\Export;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Ui\Model\Export\ConvertToCsv;
use Psr\Log\LoggerInterface;

class GridToCsv extends \Magento\Ui\Controller\Adminhtml\Export\GridToCsv
{
    /**
     * @var ManagerInterface
     */
    protected $managerInterface;
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @param Context $context
     * @param ConvertToCsv $converter
     * @param FileFactory $fileFactory
     * @param Filter|null $filter
     * @param LoggerInterface|null $logger
     * @param ManagerInterface $managerInterface
     */
    public function __construct(
        Context $context,
        ConvertToCsv $converter,
        FileFactory $fileFactory,
        Filter $filter = null,
        LoggerInterface $logger = null,
        ManagerInterface $managerInterface
    ) {
        $this->managerInterface     = $managerInterface;
        parent::__construct($context, $converter, $fileFactory, $filter, $logger);
    }

    /**
     * Execute function
     *
     * @return \Magento\Framework\App\ResponseInterface|ManagerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        try {
            $csvFile = $this->converter->getCsvFile();
            if (!empty($csvFile) && isset($csvFile['value'])) {
                return $this->fileFactory->create('export.csv', $csvFile, 'var');
            }
        } catch (\Exception $e) {
            $nameSpace = trim($e->getMessage());
            if ($nameSpace == "sales_order_invoice_grid") {
                $this->managerInterface->addWarningMessage(__('You do not have permission to export Korea invoice data.'));
                return $this->_redirect('sales/invoice/index/');
            }

            if ($nameSpace == "customer_listing") {
                $this->managerInterface->addWarningMessage(__('You do not have permission to export Korea customer data.'));
                return $this->_redirect('customer/index/index/');
            }

            if ($nameSpace == "sales_order_shipment_grid") {
                $this->managerInterface->addWarningMessage(__('You do not have permission to export Korea shipment data.'));
                return $this->_redirect('sales/shipment/index/');
            }

            if ($nameSpace == "sales_order_creditmemo_grid") {
                $this->managerInterface->addWarningMessage(__('You do not have permission to export Korea creditmemo data.'));
                return $this->_redirect('sales/creditmemo/index/');
            }
        }

        return $this->_redirect('customer/index/index/');
    }
}
