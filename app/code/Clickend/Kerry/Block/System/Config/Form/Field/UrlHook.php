<?php
namespace Clickend\Kerry\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

class UrlHook extends \Magento\Config\Block\System\Config\Form\Field
{    
	public $storeManager;
	public function __construct(
       StoreManagerInterface $storeManager   
    ) {       
  			$this->storeManager = $storeManager;
	}
    protected function _getElementHtml(AbstractElement $element)
    {
       // $element->setDisabled('disabled');
		//$element->setData('readonly', 1);
		//$element->setValue($this->_storeManager->getStore()->getBaseUrl()."rest/V1/shiping_status");
        //return $element;
		
		
		return $element->setValue("website URL");
    }
}