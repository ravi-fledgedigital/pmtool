<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model\Validation;

use Amasty\Rma\Api\Data\GuestCreateRequestInterface;
use Amasty\Rma\Model\Validation\GuestLogin\FieldValidatorProvider;
use Amasty\Rma\Model\Validation\GuestLogin\Validation\FieldValidatorInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class GuestRequestDataValidator
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly FieldValidatorProvider $fieldValidatorProvider
    ) {
    }

    public function isValid(GuestCreateRequestInterface $guestCreateRequest): bool
    {
        $isValid = true;

        $order = $this->orderRepository->get((int)$guestCreateRequest->getOrderId());

        if ($this->isFieldAvailable(FieldValidatorInterface::BILLING_FIELD)) {
            $isValid = $this->isBillingNameValid($order, $guestCreateRequest);
        }

        if ($isValid
            && !empty($guestCreateRequest->getEmail())
            && mb_strtolower($order->getCustomerEmail())
            !== mb_strtolower($guestCreateRequest->getEmail())
        ) {
            $isValid = false;
        }

        if ($isValid
            && !empty($guestCreateRequest->getZip())
            && mb_strtolower($order->getBillingAddress()->getPostcode())
            !== mb_strtolower($guestCreateRequest->getZip())
        ) {
            $isValid = false;
        }

        return $isValid;
    }

    private function isFieldAvailable(string $field): bool
    {
        $validator = $this->fieldValidatorProvider->getValidatorByType($field);
        if (null !== $validator) {
            return $validator->isFieldAvailable();
        }

        return true;
    }

    private function isBillingNameValid(
        OrderInterface $order,
        GuestCreateRequestInterface $guestCreateRequest
    ): bool {
        return mb_strtolower(trim($order->getBillingAddress()->getLastname()))
            === mb_strtolower(trim($guestCreateRequest->getBillingLastName()));
    }
}
