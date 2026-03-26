<?php

namespace OnitsukaTiger\Favorite\Block\Adminhtml\Favorite;

class Details extends \Magento\Backend\Block\Template
{
    protected $_productRepository;
	protected $_coreSession;
	protected $priceCurrency;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Catalog\Model\ProductRepository $productRepository,
		\Magento\Framework\Session\SessionManagerInterface $coreSession,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        $this->_productRepository = $productRepository;
		$this->_coreSession = $coreSession;
        $this->priceCurrency = $priceCurrency;
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        $name = $this->getProduct()->getName();
        $this->pageConfig->getTitle()->set($name);
        return parent::_prepareLayout();
    }

    public function getProduct()
    {
        $id = $this->getProductIdParam()[0];
        $product = $this->_productRepository->getById($id);

        $this->_coreSession->setFavoriteDetailsId($id);
        $this->_coreSession->setFavoriteDetailsType($product->getTypeId());

        return $product;
    }

    public function getProductIdParam()
    {
        $fkuId = $this->getRequest()->getParam('id');
        $fkuIdArr = explode("_", $fkuId);
        return $fkuIdArr;
    }

    public function getFormatedPrice($amount)
    {
        return $this->priceCurrency->convertAndFormat($amount);
    }
}
