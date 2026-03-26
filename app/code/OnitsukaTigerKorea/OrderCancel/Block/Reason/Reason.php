<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
 * @package Amasty_Rma
 */


namespace OnitsukaTigerKorea\OrderCancel\Block\Reason;

use OnitsukaTigerKorea\OrderCancel\Api\ReasonRepositoryInterface;
use Magento\Framework\View\Element\Template;

class Reason extends Template
{
    /**
     * @var ReasonRepositoryInterface
     */
    private ReasonRepositoryInterface $reasonRepository;

    public function __construct(
        ReasonRepositoryInterface $reasonRepository,
        Template\Context          $context,
        array                     $data = []
    ) {
        parent::__construct($context, $data);
        $this->reasonRepository = $reasonRepository;
    }


    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getReasons(): array
    {
        return $this->reasonRepository->getReasonsByStoreId($this->_storeManager->getStore()->getId());
    }
}
