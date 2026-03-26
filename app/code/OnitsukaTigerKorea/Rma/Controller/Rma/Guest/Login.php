<?php

namespace OnitsukaTigerKorea\Rma\Controller\Rma\Guest;

class Login extends \Magento\Framework\App\Action\Action
{
    public function execute()
    {
        return $this->_redirect('sales/guest/form');
    }
}
