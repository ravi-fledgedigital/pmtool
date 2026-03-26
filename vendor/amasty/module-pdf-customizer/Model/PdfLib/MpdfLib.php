<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Model\PdfLib;

use Mpdf\Mpdf;

class MpdfLib implements PdfInterface
{
    public const HEADER_CSS = 1;

    /**
     * Emulate Zend PDF behavior
     * Should contain HTML pages without html tag
     *
     * @var array
     */
    public $pages = [];

    /**
     * @var string
     */
    private $doctypeHeader = '';

    /**
     * @var MpdfLib
     */
    private $mpdf;

    /**
     * @var string
     */
    private $output;

    public function __construct()
    {
        $this->create();
    }

    /**
     * @return $this
     */
    public function create()
    {
        $this->output = null;
        $this->mpdf = new Mpdf([
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0
        ]);
        $this->mpdf->autoScriptToLang = true;
        $this->mpdf->autoLangToFont = true;

        return $this;
    }

    /**
     * set HTML which will be converted to PDF
     * each body tag will be processed as new page
     *
     * @param string $html
     *
     * @return $this
     */
    public function setHtml($html)
    {
        // remove doctype and html tag for multi templates in one pdf
        $html = preg_replace('~<(?:!DOCTYPE|/?(?:html))[^>]*>\s*~i', '', $html);
        $html = $this->replaceTagPreserveAttributes($html, 'dl', 'div');
        $html = $this->replaceTagPreserveAttributes($html, 'dt', 'span');
        $html = $this->replaceTagPreserveAttributes($html, 'dd', 'span');
        $this->pages[] = $html;

        return $this;
    }

    public function replaceTagPreserveAttributes(string $html, string $fromTag, string $toTag): string
    {
        $html = preg_replace_callback("/<{$fromTag}\b([^>]*)>/i", function ($matches) use ($toTag) {
            return "<{$toTag}{$matches[1]}>";
        }, $html);

        $html = preg_replace("/<\/{$fromTag}>/i", "</{$toTag}>", $html);

        return $html;
    }

    private function replaceHeader($match)
    {
        if (strpos($match[0], 'DOCTYPE') !== false) {
            $this->doctypeHeader = $match[0];
        }

        return '';
    }

    /**
     * @param string $cssString
     */
    public function setCss($cssString)
    {
        $this->mpdf->WriteHTML($cssString, self::HEADER_CSS);
    }

    /**
     * @param array $options output options
     *
     * @return string|null
     */
    public function render($options = [])
    {
        $this->mpdf->WriteHTML($this->prepareHtml());

        $this->output = $this->mpdf->Output('', 'S');

        return $this->output;
    }

    /**
     * For compatibility with default Magento PDF processor
     *
     * @return \Zend_Pdf
     */
    public function convertToZendPDF()
    {
        return new \Zend_Pdf($this->render());
    }

    /**
     * render
     *
     * @return string
     */
    protected function prepareHtml()
    {
        $html = $this->doctypeHeader . implode('', $this->pages);
        $this->pages = [];
        $this->doctypeHeader = '';

        // Replacing NNBSP with a space
        return str_replace(' ', ' ', $html);
    }
}
