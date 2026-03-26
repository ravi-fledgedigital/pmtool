<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\Request\Pending;

use Amasty\Rma\Controller\Adminhtml\Request\AbstractMassDelete;

class MassDelete extends AbstractMassDelete
{
    public const ADMIN_RESOURCE = 'Amasty_Rma::pending_delete';
}
