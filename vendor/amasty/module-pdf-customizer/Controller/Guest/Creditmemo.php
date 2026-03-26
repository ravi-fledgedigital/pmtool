<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Controller\Guest;

class Creditmemo extends \Amasty\PDFCustom\Controller\Sales\Creditmemo
{
    protected function getRedirect()
    {
        return $this->_redirect('sales/guest/form');
    }
}
