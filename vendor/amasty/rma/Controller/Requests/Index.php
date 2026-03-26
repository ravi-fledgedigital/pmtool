<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Requests;

use Amasty\Rma\Controller\FrontendRma;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var FrontendRma
     */
    private $frontendRma;

    public function __construct(
        FrontendRma $frontendRma,
        Context $context
    ) {
        parent::__construct($context);
        $this->frontendRma = $frontendRma;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     */
    public function execute()
    {
        return $this->resultRedirectFactory->create()->setPath($this->frontendRma->getReturnRequestHomeUrl());
    }
}
