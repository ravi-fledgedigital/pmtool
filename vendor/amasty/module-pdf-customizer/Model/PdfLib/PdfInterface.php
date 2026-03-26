<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Model\PdfLib;

interface PdfInterface
{
    public function create();

    /**
     * set HTML which will be converted to PDF
     * each body tag will be processed as new page
     *
     * @param string $html
     *
     * @return PdfInterface
     */
    public function setHtml($html);

    /**
     * @param string $cssString
     */
    public function setCss($cssString);

    /**
     * @param array $options output options
     *
     * @return string|null
     */
    public function render($options = []);

    /**
     * For compatibility with default Magento PDF processor
     *
     * @return \Zend_Pdf
     */
    public function convertToZendPDF();
}
