<?php

namespace Cpss\JsBarcode\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * class helper data of js barcode options
 */
class Data extends AbstractHelper
{
    const SEC_GROUP = 'barcode_section/barcode_group/';
    const FORMAT = 'format';
    const WIDTH = 'width';
    const HEIGHT = 'height';
    const MARGIN = 'margin';
    const BG = 'background';
    const LCOLOR = 'linecolor';
    const STEXT = 'displayvalue';
    const TALIGN = 'textalign';
    const FONT = 'font';
    const FOPT = 'fontoptions';
    const FSIZE = 'fontsize';
    const TMARGIN = 'textmargin';

    protected $_scopeConfig;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * Js barcode code format
     * @param void
     * @return mixed
     */
    public function getFormat()
    {
        return $this->_scopeConfig->getValue(self::SEC_GROUP . self::FORMAT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Js barcode width
     * @param void
     * @return mixed
     */
    public function getWidth()
    {
        return $this->_scopeConfig->getValue(self::SEC_GROUP . self::WIDTH, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Js barcode height
     * @param void
     * @return mixed
     */
    public function getHeight()
    {
        return $this->_scopeConfig->getValue(self::SEC_GROUP . self::HEIGHT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Js barcode margin
     * @param void
     * @return mixed
     */
    public function getMargin()
    {
        return $this->_scopeConfig->getValue(self::SEC_GROUP . self::MARGIN, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Js barcode background
     * @param void
     * @return mixed
     */
    public function getBackground()
    {
        return $this->_scopeConfig->getValue(self::SEC_GROUP . self::BG, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Js barcode line color
     * @param void
     * @return mixed
     */
    public function getLineColor()
    {
        return $this->_scopeConfig->getValue(self::SEC_GROUP . self::LCOLOR, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Js barcode show text
     * @param void
     * @return mixed
     */
    public function isShowText()
    {
        return $this->_scopeConfig->getValue(self::SEC_GROUP . self::STEXT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Js barcode text align
     * @param void
     * @return mixed
     */
    public function getTextAlign()
    {
        return $this->_scopeConfig->getValue(self::SEC_GROUP . self::TALIGN, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Js barcode font
     * @param void
     * @return mixed
     */
    public function getFont()
    {
        return $this->_scopeConfig->getValue(self::SEC_GROUP . self::FONT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Js barcode font style
     * @param void
     * @return mixed
     */
    public function getFontStyle()
    {
        return $this->_scopeConfig->getValue(self::SEC_GROUP . self::FOPT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Js barcode font size
     * @param void
     * @return mixed
     */
    public function getFontSize()
    {
        return $this->_scopeConfig->getValue(self::SEC_GROUP . self::FSIZE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Js barcode margin
     * @param void
     * @return mixed
     */
    public function getTextMargin()
    {
        return $this->_scopeConfig->getValue(self::SEC_GROUP . self::TMARGIN, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Js barcode options
     * @param void
     * @return JSON
     */
    public function getJsBarcodeOptionsToJson()
    {
        $options = [
            "format"        => $this->getFormat(),
            "width"         => (int) $this->getWidth(),
            "height"        => (int) $this->getHeight(),
            "margin"        => (int) $this->getMargin(),
            "background"    => $this->getBackground(),
            "lineColor"     => $this->getLineColor(),
            "displayValue"  => $this->isShowText(),
            "textAlign"     => $this->getTextAlign(),
            "font"          => $this->getFont(),
            "fontOptions"   => $this->getFontStyle(),
            "fontSize"      => $this->getFontSize(),
            "textMargin"    => $this->getTextMargin(),
        ];

        return json_encode($options);
    }
}
