<?php
namespace OnitsukaTiger\NetsuiteOrderSync\Plugin\Controller\Adminhtml\Job;

use OnitsukaTiger\NetsuiteOrderSync\Model\Export\Order\Shipment\Fields\StockLocation\Options;

class FilterStockLocation
{
    const STOCK_LOCATION = 'stock_location';

    /**
     * @var Options
     */
    protected $stockLocationOptions;

    /**
     * FilterStockLocation constructor.
     * @param Options $stockLocationOptions
     */
    public function __construct(
        Options $stockLocationOptions
    ) {
        $this->stockLocationOptions = $stockLocationOptions;
    }

    /**
     * @param \Firebear\ImportExport\Controller\Adminhtml\Job\Downfiltres $subject
     * @param $resultJson
     * @return mixed
     */
    public function afterExecute(\Firebear\ImportExport\Controller\Adminhtml\Job\Downfiltres $subject, $resultJson)
    {
        $result = [];
        if ($subject->getRequest()->isAjax()) {
            $attribute = $subject->getRequest()->getParam('attribute');
            if ($attribute == self::STOCK_LOCATION) {
                $result['field'] = $attribute;
                $result['type'] = 'select';
                $result['select'] = $this->stockLocationOptions->toOptionArray();

                return $resultJson->setData($result);
            }

            return $resultJson;
        }
    }
}
