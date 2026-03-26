<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Block\Adminhtml\Template\Preview;

/**
 * Copy of Magento core file from v2.4.4.
 * Since m2.4.5 Magento add purifier to filter malicious code.
 * Purifier remove some necessary blocks, such as 'style'.
 * @see \Magento\Framework\Filter\Input\MaliciousCode
 */
class MaliciousCode
{
    /**
     * @var string[]
     */
    protected $expressions = [
        //comments, must be first
        '/(\/\*.*\*\/)/Us',
        //tabs
        '/(\t)/',
        //javasript prefix
        '/(javascript\s*:)/Usi',
        //import styles
        '/(@import)/Usi',
        //js in the style attribute
        '/style=[^<]*((expression\s*?\([^<]*?\))|(behavior\s*:))[^<]*(?=\/*\>)/Uis',
        //js attributes
        '/(ondblclick|onclick|onkeydown|onkeypress|onkeyup|onmousedown|onmousemove|onmouseout|onmouseover|onmouseup|'.
        'onload|onunload|onerror)=[^<]*(?=\/*\>)/Uis',
        //tags
        '/<\/?(script|meta|link|frame|iframe|object).*>/Uis',
        //scripts
        '/<\?\s*?(php|=).*>/Uis',
        //base64 usage
        '/src=[^<]*base64[^<]*(?=\/*\>)/Uis',
    ];

    /**
     * @param $value
     * @return array|mixed|string|string[]|null
     */
    public function filter($value)
    {
        $replaced = 0;
        do {
            $value = preg_replace($this->expressions, '', $value ?? '', -1, $replaced);
        } while ($replaced !== 0);

        return $value;
    }
}
