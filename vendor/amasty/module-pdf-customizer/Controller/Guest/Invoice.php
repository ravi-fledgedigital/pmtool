<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Controller\Guest;

class Invoice extends \Amasty\PDFCustom\Controller\Sales\Invoice
{
    protected function getRedirect()
    {
        return $this->_redirect('sales/guest/form');
    }
}
