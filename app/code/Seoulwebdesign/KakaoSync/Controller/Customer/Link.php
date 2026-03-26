<?php
/**
 * Copyright © a All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Seoulwebdesign\KakaoSync\Controller\Customer;

class Link extends \Seoulwebdesign\KakaoSync\Controller\Manage
{
    /**
     * Managing newsletter subscription page
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();

//        if ($block = $this->_view->getLayout()->getBlock('customer_newsletter')) {
//            $block->setRefererUrl($this->_redirect->getRefererUrl());
//        }
        $this->_view->getPage()->getConfig()->getTitle()->set(__('Kakao Sync'));
        $this->_view->renderLayout();
    }
}
