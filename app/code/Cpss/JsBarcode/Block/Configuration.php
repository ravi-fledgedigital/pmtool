<?php
namespace Cpss\JsBarcode\Block;
 
class Configuration extends \Magento\Framework\View\Element\Template
{
     public function __construct(\Magento\Framework\View\Element\Template\Context $context)
	{
		parent::__construct($context);
	}

     /**
      * Load js barcode 
      * @param string $text
      * @return html
      */
     public function loadBarcode($text)
     {
          $block = $this->getLayout()
                         ->createBlock("\Magento\Framework\View\Element\Template")
                         ->setData('text', $text)
                         ->setTemplate("Cpss_JsBarcode::barcode.phtml");
          
          return $block->toHtml();
     }

}


