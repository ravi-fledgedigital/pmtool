<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Entity;

use OnitsukaTiger\OrderAttribute\Api\Data\EntityDataInterface;
use OnitsukaTiger\OrderAttribute\Api\GuestCheckoutDataRepositoryInterface;
use OnitsukaTiger\OrderAttribute\Api\CheckoutDataRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\QuoteRepository;

class GuestCheckoutDataRepository implements GuestCheckoutDataRepositoryInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var CheckoutDataRepositoryInterface
     */
    private $repository;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        QuoteRepository $quoteRepository,
        CheckoutDataRepositoryInterface $repository
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->repository = $repository;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @inheritdoc
     */
    public function save(
        $onitsukatigerCartId,
        $checkoutFormCode,
        $shippingMethodCode,
        EntityDataInterface $entityData
    ) {
        if ($parentId = $this->quoteIdMaskFactory->create()->load($onitsukatigerCartId, 'masked_id')->getQuoteId()) {
            try {
                $quote = $this->quoteRepository->get($parentId);

                return $this->repository->save($parentId, $checkoutFormCode, $shippingMethodCode, $entityData);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                throw new \Magento\Framework\Exception\InputException(__('Quote doesn\'t exist.'));
            }
        }

        throw new \Magento\Framework\Exception\InputException(__('Quote doesn\'t exist.'));
    }
}
