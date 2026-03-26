<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Plugin\AdminOrder;

use Magento\Backend\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Sales\Model\Order;

class CreatePlugin
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Session\Quote
     */
    private $quoteSession;

    public function __construct(
        RequestInterface $request,
        Session $session,
        \Magento\Backend\Model\Session\Quote $quoteSession
    ) {
        $this->request = $request;
        $this->session = $session;
        $this->quoteSession = $quoteSession;
    }

    public function afterInitFromOrder(Create $subject, $result, Order $order)
    {
        if ($requestId = (int)$this->request->getParam('rma_request_id')) {
            $backToRmaFlow = $this->session->getBackToRmaFlow() ?? [];
            $backToRmaFlow[(int)$order->getId()] = [
                'request_id' => $requestId,
                'complete' => false,
            ];
            $this->session->setBackToRmaFlow($backToRmaFlow);
        }

        return $result;
    }
}
