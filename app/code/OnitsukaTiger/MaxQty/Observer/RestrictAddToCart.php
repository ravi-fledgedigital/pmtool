<?php

namespace OnitsukaTiger\MaxQty\Observer;

use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\ObserverInterface;

class RestrictAddToCart implements ObserverInterface
{
    protected $cart;
    protected $redirect;
    protected $request;
    protected $product;
    protected $configurableproduct;

    public function __construct(
        RedirectInterface $redirect,
        Cart $cart,
        RequestInterface $request,
        Product $product,
        Configurable $configurableproduct,
        private \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->redirect = $redirect;
        $this->cart = $cart;
        $this->request = $request;
        $this->product = $product;
        $this->configurableproduct = $configurableproduct;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $postValues = $this->request->getPostValue();
        $productId = $postValues['product'];
        $addProduct = $this->product->load($productId);

        if ($addProduct->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE && isset($postValues['super_attribute'])) {
            $attributes = $postValues['super_attribute'];
            $childProduct = $this->configurableproduct->getProductByAttributes($attributes, $addProduct);
            if ($childProduct && $childProduct->getId() && $childProduct->getForceOosToggle()) {
                $this->messageManager->addError(__('The requested product is out of stock.'));
                $observer->getRequest()->setParam('product', false);
                return $this;
            }
        }
    }
}
