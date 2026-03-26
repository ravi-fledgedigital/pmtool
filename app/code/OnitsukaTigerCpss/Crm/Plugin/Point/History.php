<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTigerCpss\Crm\Plugin\Point;

use Cpss\Crm\Block\Member\Info as MemberInfo;
use Cpss\Crm\Controller\Point\History as PointHistory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;

class History extends \Cpss\Crm\Controller\Point\History
{
    /**
     * @var MemberInfo
     */
    protected $memberInfo;

    /**
     * History construct class
     *
     * @param Context $context
     * @param Session $customerSession
     * @param MemberInfo $memberInfo
     */
    public function __construct(
        Context    $context,
        Session    $customerSession,
        MemberInfo $memberInfo
    ) {
        parent::__construct($context, $customerSession);
        $this->memberInfo = $memberInfo;
    }

    /**
     * Around Execute
     *
     * @param PointHistory $subject
     * @param callable $proceed
     * @return ResponseInterface|ResultInterface|Page
     */
    public function aroundExecute(PointHistory $subject, callable $proceed)
    {
        if (!$this->customerSession->isLoggedIn() || !$this->memberInfo->isModuleEnabled()) {
            return $this->_redirect('customer/account');
        }

        if (!$this->customerSession->getPointServiceEnabled()) {
            return $this->_redirect('customer/account');
        }

        return $this->resultFactory->create(ResultFactory::TYPE_PAGE);
    }
}
