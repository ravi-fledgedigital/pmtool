<?php

declare(strict_types=1);

namespace OnitsukaTigerIndo\SizeConverter\Helper;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Data
 * @package OnitsukaTiger\Catalog\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $indoSizeFactory;

    public function __construct( 
        \OnitsukaTigerIndo\SizeConverter\Model\IndoSizeFactory $indoSizeFactory
    )
    {
        $this->indoSizeFactory = $indoSizeFactory;
    }

     /**
     * @return array
     */
    public function getIndoSizes() {
        $sizeOptions = [];
        
        $collection = $this->indoSizeFactory->create()->getCollection();
        if ($collection && $collection->getSize() > 0) {
            foreach ($collection as $indoSize) {
                $sizeOptions[$indoSize->getEnglishSize()] = $indoSize->getEuroSize();
            }
        }

        return $sizeOptions;
    }
}

