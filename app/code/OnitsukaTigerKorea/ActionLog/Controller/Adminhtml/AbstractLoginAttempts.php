<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
 * @package Admin Actions Log for Magento 2
 */
declare(strict_types=1);

namespace OnitsukaTigerKorea\ActionLog\Controller\Adminhtml;

use Magento\Backend\App\Action;

abstract class AbstractLoginAttempts extends Action
{
    public const ADMIN_RESOURCE = 'OnitsukaTigerKorea_ActionLog::amaudit';
}
