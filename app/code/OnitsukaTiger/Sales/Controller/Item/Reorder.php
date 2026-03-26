<?php

namespace OnitsukaTiger\Sales\Controller\Item;

class Reorder extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * Reorder constructor.
     * @param Action\Context $context
     * @param Registry $registry
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository
    ) {
        $this->orderItemRepository = $orderItemRepository;
        parent::__construct($context);
    }

    /**
     * Action for reorder
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $itemId = (int)$this->_request->getParam('item_id');
        if (!$itemId) {
            /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
            $resultForward = $this->resultForwardFactory->create();
            return $resultForward->forward('noroute');
        }

        $orderItem = $this->orderItemRepository->get($itemId);

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        /* @var $cart \Magento\Checkout\Model\Cart */
        $cart = $this->_objectManager->get(\Magento\Checkout\Model\Cart::class);

        try {
            $cart->addOrderItem($orderItem);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            if ($this->_objectManager->get(\Magento\Checkout\Model\Session::class)->getUseNotice(true)) {
                $this->messageManager->addNoticeMessage($e->getMessage());
            } else {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
            return $resultRedirect->setPath('sales/order/history');
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('We can\'t add this item to your shopping cart right now.')
            );
            return $resultRedirect->setPath('checkout/cart');
        }

        $cart->save();
        return $resultRedirect->setPath('checkout/cart');
    }
}
