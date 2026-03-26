<?php
declare(strict_types=1);

namespace OnitsukaTiger\ImportExport\Plugin\Model\Export;

use Firebear\ImportExport\Model\Export\Product;

class ProductPlugin
{
    public function after_customFieldsMapping(Product $subject, $result){
        if(isset($result['configurable_variations']) && mb_strlen($result['configurable_variations']) > \OpenSpout\Writer\XLSX\Manager\WorksheetManager::MAX_CHARACTERS_PER_CELL) {
            unset($result['configurable_variations']);
        }
        return $result;
    }
}
