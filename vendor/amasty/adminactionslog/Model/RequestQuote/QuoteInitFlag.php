<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Admin Actions Log for Magento 2
 */

namespace Amasty\AdminActionsLog\Model\RequestQuote;

class QuoteInitFlag
{
    /**
     * @var bool
     */
    private bool $isQuoteInit = false;

    public function isQuoteInit(): bool
    {
        return $this->isQuoteInit;
    }

    public function setIsQuoteInit(bool $isQuoteInit): void
    {
        $this->isQuoteInit = $isQuoteInit;
    }
}
