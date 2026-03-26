<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Model;

class ComponentChecker
{
    /**
     * @return bool
     */
    public function isComponentsExist()
    {
        try {
            $classExists = class_exists(\Dompdf\Dompdf::class) || class_exists(\Mpdf\Mpdf::class);
        } catch (\Exception $e) {
            $classExists = false;
        }

        return $classExists;
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getComponentsErrorMessage()
    {
        return __(
            "To use PDF customizer, please install the library dompdf/dompdf "
            . "or mpdf/mpdf since it is required for proper "
            . "PDF customizer functioning. To do this, run the command ".
            "\"composer require dompdf/dompdf\" or \"composer require mpdf/mpdf\" in the main site folder."
        );
    }
}
