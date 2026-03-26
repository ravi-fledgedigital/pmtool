<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
declare(strict_types=1);

namespace OnitsukaTiger\OrderAttribute\Model\Entity\Adapter\Quote\Plugin\Api;

use OnitsukaTiger\OrderAttribute\Model\Entity\Adapter\Quote\Adapter;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class QuoteRepository
{
    /**
     * @var Adapter
     */
    private $adapter;

    /**
     * @var CartInterface
     */
    protected $currentQuote;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @param CartRepositoryInterface $subject
     * @param CartInterface $quote
     * @return CartInterface
     */
    public function afterGet(CartRepositoryInterface $subject, CartInterface $quote): CartInterface
    {
        $this->adapter->addExtensionAttributesToQuote($quote);

        return $quote;
    }

    /**
     * @param CartRepositoryInterface $subject
     * @param CartInterface $quote
     * @return CartInterface
     */
    public function afterGetForCustomer(CartRepositoryInterface $subject, CartInterface $quote): CartInterface
    {
        $this->adapter->addExtensionAttributesToQuote($quote);

        return $quote;
    }

    /**
     * @param CartRepositoryInterface $subject
     * @param CartInterface $quote
     * @return void
     */
    public function beforeSave(CartRepositoryInterface $subject, CartInterface $quote): void
    {
        $this->currentQuote = $quote;
    }

    /**
     * @param CartRepositoryInterface $subject
     * @throws CouldNotSaveException
     * @return void
     */
    public function afterSave(CartRepositoryInterface $subject): void
    {
        $this->adapter->saveQuoteValues($this->currentQuote);
    }
}
