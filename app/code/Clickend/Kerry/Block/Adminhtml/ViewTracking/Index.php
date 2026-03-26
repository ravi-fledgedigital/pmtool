<?php

namespace Clickend\Kerry\Block\Adminhtml\ViewTracking;

class Index extends \Magento\Backend\Block\Widget\Container
    {
	protected $request;
	public function __construct(\Magento\Backend\Block\Widget\Context $context,\Magento\Framework\App\Request\Http $request,array $data = [])
    {
      parent::__construct($context, $data);
		 $this->request = $request;
    }
    public function getIddata()
    {
    $this->request->getParams();
        return $this->request->getParam('track_id');
    }
}
