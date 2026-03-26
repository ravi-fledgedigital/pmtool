<?php

namespace OnitsukaTigerKorea\OrderCancel\Controller\Adminhtml;

abstract class AbstractReason extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'OnitsukaTigerKorea_OrderCancel::reason::reason';
}
