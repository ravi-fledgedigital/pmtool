<?php
/**
 * Copyright © a All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Seoulwebdesign\KakaoSync\Controller\Customer;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

class LinkAccount implements HttpGetActionInterface
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var Context
     */
    protected $context;
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * Constructor
     *
     * @param PageFactory $resultPageFactory
     * @param Context $context
     * @param RequestInterface $request
     */
    public function __construct(
        PageFactory $resultPageFactory,
        Context $context,
        RequestInterface $request
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->context = $context;
        $this->request = $request;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        return $this->resultPageFactory->create();
    }
}
