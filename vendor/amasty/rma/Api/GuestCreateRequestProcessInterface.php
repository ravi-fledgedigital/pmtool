<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Api;

/**
 * Interface GuestCreateRequestProcessInterface
 */
interface GuestCreateRequestProcessInterface
{
    /**
     * @param \Amasty\Rma\Api\Data\GuestCreateRequestInterface $guestCreateRequest
     *
     * @return string|bool
     */
    public function process(\Amasty\Rma\Api\Data\GuestCreateRequestInterface $guestCreateRequest);

    /**
     * @return \Amasty\Rma\Api\Data\GuestCreateRequestInterface
     */
    public function getEmptyCreateRequest();

    /**
     * @param string $secretKey
     *
     * @return bool|int
     */
    public function getOrderIdBySecretKey($secretKey);

    /**
     * @param string $secretKey
     *
     * @return void
     */
    public function deleteBySecretKey($secretKey);
}
