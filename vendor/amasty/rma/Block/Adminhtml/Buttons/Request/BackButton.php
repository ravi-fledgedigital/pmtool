<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Block\Adminhtml\Buttons\Request;

use Amasty\Rma\Block\Adminhtml\Buttons\GenericButton;
use Amasty\Rma\Model\Request\Repository;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class BackButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @var RedirectInterface
     */
    protected $redirect;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    public function __construct(
        Context $context,
        RedirectInterface $redirect,
        OrderRepositoryInterface $orderRepository,
        Repository $requestRepository
    ) {
        parent::__construct($context, $orderRepository, $requestRepository);
        $this->session = $context->getBackendSession();
        $this->redirect = $redirect;
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        $onClick = sprintf("location.href = '%s'", $this->getBackUrl());
        $data = [
            'label' => __('Back'),
            'class' => 'action- scalable back',
            'id' => 'back',
            'on_click' => $onClick,
            'sort_order' => 20,
        ];

        return $data;
    }

    /**
     * @return string
     */
    public function getBackUrl()
    {
        $returnUrl = $this->session->getAmRmaReturnUrl();

        if (!$returnUrl) {
            $returnUrl = $this->redirect->getRefererUrl();
            $this->session->setAmRmaReturnUrl($returnUrl);
        }

        return $returnUrl;
    }
}
