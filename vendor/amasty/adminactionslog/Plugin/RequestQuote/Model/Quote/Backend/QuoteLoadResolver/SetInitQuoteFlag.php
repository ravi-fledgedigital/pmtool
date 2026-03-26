<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Admin Actions Log for Magento 2
 */

namespace Amasty\AdminActionsLog\Plugin\RequestQuote\Model\Quote\Backend\QuoteLoadResolver;

use Amasty\AdminActionsLog\Model\RequestQuote\QuoteInitFlag;
use Amasty\RequestQuote\Model\Quote;
use Amasty\RequestQuote\Model\Quote\Backend\QuoteLoadResolver;

class SetInitQuoteFlag
{
    public function __construct(
        private readonly QuoteInitFlag $quoteInitFlag
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeInitQuote(
        QuoteLoadResolver $subject
    ): void {
        $this->quoteInitFlag->setIsQuoteInit(true);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterInitQuote(
        QuoteLoadResolver $subject,
        Quote $result
    ): Quote {
        $this->quoteInitFlag->setIsQuoteInit(false);

        return $result;
    }
}
