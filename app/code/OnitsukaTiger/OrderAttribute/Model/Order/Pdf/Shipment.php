<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Order\Pdf;

class Shipment extends \Magento\Sales\Model\Order\Pdf\Shipment
{
    use Traits\AbstractPdfTrait;

    /**
     * @return bool
     */
    protected function isPrintAttributesAllowed()
    {
        return (bool)$this->configProvider->isIncludeToShipmentPdf();
    }
}
