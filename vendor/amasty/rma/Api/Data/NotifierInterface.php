<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Api\Data;

interface NotifierInterface
{
    public function notify(
        \Amasty\Rma\Api\Data\RequestInterface $request,
        \Amasty\Rma\Api\Data\MessageInterface $message
    ): void;
}
