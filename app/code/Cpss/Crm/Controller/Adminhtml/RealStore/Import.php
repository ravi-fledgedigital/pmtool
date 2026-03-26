<?php

namespace Cpss\Crm\Controller\Adminhtml\RealStore;

class Import extends \Magento\Backend\App\Action
{
    const FILE_PATH = 'realstore/info.csv';
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;
    protected $fileHelper;
    protected $logger;
    protected $realStore;

    /**
     * @param \Magento\Backend\App\Action\Context        $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Cpss\Crm\Helper\File $file,
        \Psr\Log\LoggerInterface $loggerInterface,
        \Cpss\Crm\Model\ResourceModel\RealStore $realStore
    ) {
        parent::__construct($context);
        $this->_resultPageFactory = $resultPageFactory;
        $this->fileHelper = $file;
        $this->logger = $loggerInterface;
        $this->realStore = $realStore;
    }

    public function execute()
    {
        try {
            $files = $this->fileHelper->scanFiles('realstore');
            $toUpdate = [];
            foreach ($files as $file) {
                $parts = explode('.', $file);
                $ext = end($parts);
                if ('csv' != strtolower($ext)) {
                    continue;
                }

                $content = $this->fileHelper->readFile($file);
                $data = $this->getData($content); // Convert content to array

                if (!empty($data)) {
                    try {
                        $this->logger->info("RealShop Cronjob: {$file}");
                        for ($i = 0; $i < count($data); $i++) {
                            if ($i <= 0) { // Validate header
                                if (!$this->validateHeader($data)) {
                                    $this->logger->warning("RealShop Cronjob: Invalid Header: {$file}");
                                    continue;
                                }
                            } else {
                                $toUpdate[] = [
                                    'shop_id' => $data[$i][0],
                                    'shop_status' => $data[$i][1],
                                    'shop_name' => $data[$i][8]
                                ];
                            }
                        }
                    } catch (\Exception $e) {
                        $this->logger->critical("RealShop Cronjob: Invalid File: {$file}");
                    }
                }
            }

            if (!empty($toUpdate)) {
                $this->realStore->getConnection()->insertOnDuplicate('crm_real_stores', $toUpdate);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return $this->_redirect('admincrm/realstore/index');
    }


    /**
     * Get Data (Converts content to array)
     *
     * @param  mixed $content
     * @return array
     */
    public function getData($content)
    {
        $row = str_getcsv($content, "\n");
        $length = count($row);

        $data = [];
        for ($i = 0; $i < $length; $i++) {
            $rowData = str_getcsv($row[$i], ",");
            $data[] = $rowData;
        }

        return $data;
    }


    /**
     * Validate header
     *
     * @param  array $header
     * @return bool
     */
    public function validateHeader($header)
    {
        try {
            return ($header[0][0] == "店舗ID" && $header[0][1] == "店舗ステータス" && $header[0][8] == "店舗名");
        } catch (\Exception $e) {
            return false;
        }
    }
}
