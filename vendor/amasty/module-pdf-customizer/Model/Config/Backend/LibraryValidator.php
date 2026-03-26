<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\ValidatorException;

class LibraryValidator extends Value
{
    /**
     * @return void
     * @throws ValidatorException
     */
    public function beforeSave(): void
    {
        $classExistsDompdf = class_exists(\Dompdf\Dompdf::class);
        $classExistsMpdf = class_exists(\Mpdf\Mpdf::class);
        $value = $this->getValue();
        if (empty($value)) {
            return;
        }

        if ($value === 'dompdf' && !$classExistsDompdf) {
            throw new ValidatorException($this->getComponentsErrorMessage($value));
        }

        if ($value === 'mpdf' && !$classExistsMpdf) {
            throw new ValidatorException($this->getComponentsErrorMessage($value));
        }
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    private function getComponentsErrorMessage($value)
    {
        return __(
            "To use this library, please install the library ". $value ."/". $value
            . " it is required for proper "
            . "PDF customizer functioning. To do this, run the command ".
            "\"composer require ". $value ."/". $value ."\" in the main site folder."
        );
    }
}
