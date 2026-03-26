<?php
declare(strict_types=1);
namespace OnitsukaTigerKorea\Rma\Plugin\Model\Order;

use Amasty\Rma\Api\Data\ReturnOrderItemInterfaceFactory;
use Amasty\Rma\Model\OptionSource\NoReturnableReasons;
use Amasty\Rma\Model\ReturnRules\ReturnRulesProcessor;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderItemRepositoryInterface;

class CreateReturnProcessor {
    /**
     * @var OrderItemRepositoryInterface
     */
    public $orderItemRepository;

    /**
     * @var ProductRepositoryInterface
     */
    public $productRepository;

    /**
     * @var ReturnOrderItemInterfaceFactory
     */
    public $returnOrderItemFactory;

    /**
     * @var ReturnRulesProcessor
     */
    public $returnRulesProcessor;

    /**
     * @param ReturnOrderItemInterfaceFactory $returnOrderItemFactory
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param ProductRepositoryInterface $productRepository
     * @param ReturnRulesProcessor $returnRulesProcessor
     */
    public function __construct(
        ReturnOrderItemInterfaceFactory $returnOrderItemFactory,
        OrderItemRepositoryInterface $orderItemRepository,
        ProductRepositoryInterface $productRepository,
        ReturnRulesProcessor $returnRulesProcessor
    )
    {
        $this->returnOrderItemFactory = $returnOrderItemFactory;
        $this->orderItemRepository = $orderItemRepository;
        $this->productRepository = $productRepository;
        $this->returnRulesProcessor = $returnRulesProcessor;
    }

    public function afterProcess(\Amasty\Rma\Model\Order\CreateReturnProcessor $subject, $result, $orderId, $isAdmin = false){
        if(!$result) {
            return $result;
        }
        $items = $result->getItems();
        $alreadyRequestedItem = $subject->getAlreadyRequestedItems($orderId);
        if (!$isAdmin) {
            foreach($items as $returnItem){
                $orderItem = $this->orderItemRepository->get($returnItem->getItem()->getParentItemId());
                $rmaQty = 0;
                if (isset($alreadyRequestedItem[$returnItem->getItem()->getItemId()]['qty'])) {
                    $rmaQty = $alreadyRequestedItem[$returnItem->getItem()->getItemId()]['qty'];
                }
                $availableQty = $returnItem->getAvailableQty();
                if($returnItem->getAvailableQty()){
                    $orderAvailableQty = $returnItem->getAvailableQty() + $orderItem->getQtyPartiallyCanceled();
                }else {
                    $orderAvailableQty = $orderItem->getQtyShipped() - $orderItem->getQtyCanceled() - $orderItem->getQtyRefunded() + $orderItem->getQtyPartiallyCanceled();
                }
                if($availableQty <= 0.0001) {
                    if ($rmaQty == 0) {
                        $returnItem->setIsReturnable(false)
                            ->setNoReturnableReason(NoReturnableReasons::REFUNDED);
                    } else {
                        $returnItem->setIsReturnable(false)
                            ->setNoReturnableReason(NoReturnableReasons::ALREADY_RETURNED)
                            ->setNoReturnableData($alreadyRequestedItem[$returnItem->getItem()->getItemId()]['requests']);
                    }
                } else {
                    $isAllowedParentProduct = true;

                    if ($returnItem->getItem()->getParentItemId()) {
                        try {
                            $parentProduct = $this->productRepository->getById($orderItem->getProductId());
                        } catch (NoSuchEntityException $exception) {
                            $parentProduct = false;
                        }

                        if ($parentProduct) { // no reason to apply rules if no product, order will be checked with child
                            $parentReturnItem = $this->returnOrderItemFactory->create();
                            $parentReturnItem->setItem($orderItem->getParentItem())
                                ->setProductItem($parentProduct)
                                ->setPurchasedQty($orderItem->getQtyShipped());
                            $isAllowedParentProduct = $this->returnRulesProcessor
                                ->processReturn($result, $parentReturnItem);
                        }
                    }

                    if ($isAdmin
                        || ($isAllowedParentProduct
                            && $this->returnRulesProcessor->processReturn($result, $returnItem))
                    ) {
                        $returnItem->setIsReturnable(true)
                            ->setAvailableQty($orderAvailableQty);
                    } elseif ($returnItem->getItem()->getPrice() !== (float)$returnItem->getItem()->getOriginalPrice()) {
                        $returnItem->setIsReturnable(false)
                            ->setNoReturnableReason(NoReturnableReasons::ITEM_WAS_ON_SALE);
                    } else {
                        $returnItem->setIsReturnable(false)
                            ->setNoReturnableReason(NoReturnableReasons::EXPIRED_PERIOD);
                    }
                }
            }
        }
        return $result;
    }
}
