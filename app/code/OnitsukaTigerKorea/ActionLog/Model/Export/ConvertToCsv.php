<?php
/** phpcs:ignoreFile */

namespace OnitsukaTigerKorea\ActionLog\Model\Export;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Ui\Model\Export\MetadataProvider;

class ConvertToCsv extends \Magento\Ui\Model\Export\ConvertToCsv
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var string[]
     */
    protected $restrictExportGridData = [
        "sales_order_invoice_grid",
        "customer_listing",
        "sales_order_shipment_grid",
        "sales_order_creditmemo_grid"
    ];

    /**
     * @param Filesystem $filesystem
     * @param RequestInterface $request
     * @param Filter $filter
     * @param MetadataProvider $metadataProvider
     * @param int $pageSize
     * @throws FileSystemException
     */
    public function __construct(
        Filesystem $filesystem,
        RequestInterface $request,
        Filter $filter,
        MetadataProvider $metadataProvider,
        $pageSize = 200
    ) {
        $this->request    = $request;
        parent::__construct($filesystem, $filter, $metadataProvider, $pageSize);
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getCsvFile()
    {
        $namespace   = $this->request->getParam('namespace');
        if (in_array($namespace, $this->restrictExportGridData)) {
            $component = $this->filter->getComponent();

            $name = md5(microtime());
            $file = 'export/' . $component->getName() . $name . '.csv';

            $this->filter->prepareComponent($component);
            $this->filter->applySelectionOnTargetProvider();
            $dataProvider = $component->getContext()->getDataProvider();
            $fields = $this->metadataProvider->getFields($component);
            $options = $this->metadataProvider->getOptions();

            $this->directory->create('export');
            $stream = $this->directory->openFile($file, 'w+');
            $stream->lock();
            $stream->writeCsv($this->metadataProvider->getHeaders($component));
            $i = 1;
            $searchCriteria = $dataProvider->getSearchCriteria()
                ->setCurrentPage($i)
                ->setPageSize($this->pageSize);
            $totalCount = (int) $dataProvider->getSearchResult()->getTotalCount();
            $csvItems = 0;
            $isKrItemsToExport = true;
            while ($totalCount > 0) {
                $items = $dataProvider->getSearchResult()->getItems();
                foreach ($items as $item) {
                    $this->metadataProvider->convertDate($item, $component->getName());
                    if (
                        (isset($item['website_id']) && $item['website_id'] != 4) ||
                        isset($item['store_id']) && $item['store_id'] != 5
                    ) {
                        $csvItems++;
                        $stream->writeCsv($this->metadataProvider->getRowData($item, $fields, $options));
                        $isKrItemsToExport = false;
                    }
                }
                $searchCriteria->setCurrentPage(++$i);
                $totalCount = $totalCount - $this->pageSize;
            }
            if ($isKrItemsToExport) {
                throw new InputException(__($namespace));
            }
            $stream->unlock();
            $stream->close();
            if ($csvItems > 0) {
                return [
                    'type' => 'filename',
                    'value' => $file,
                    'rm' => true  // can delete file after use
                ];
            }
            return [];
        }
        return parent::getCsvFile();
    }
}
