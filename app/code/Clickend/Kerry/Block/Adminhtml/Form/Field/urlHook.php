<?php
namespace Clickend\Kerry\Block\Adminhtml\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;

class urlHook extends \Magento\Config\Block\System\Config\Form\Field
{    
	public $_storeManager;
	public function __construct(
      \Magento\Store\Model\StoreManagerInterface $storeManager      
    ) {       
  			$this->_storeManager=$storeManager;
	}
    protected function _getElementHtml(AbstractElement $element)
    {    
		$baseURL = $this->getBaseUrl();
        $html = $element->getElementHtml();
		 $html .=$baseURL."rest/V1/shiping_status";		
        return $html;

    }
}