<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Admin Actions Log for Magento 2
 */

namespace Amasty\AdminActionsLog\Logging\Entity\SaveHandler\Amasty\RequestQuote\Model;

use Amasty\AdminActionsLog\Api\Logging\MetadataInterface;
use Amasty\AdminActionsLog\Logging\Entity\SaveHandler\Common;
use Amasty\AdminActionsLog\Logging\Util\Ignore\ArrayFilter;
use Amasty\AdminActionsLog\Model\LogEntry\LogEntry;
use Amasty\AdminActionsLog\Model\RequestQuote\QuoteInitFlag;
use Amasty\RequestQuote\Model\Quote as RequestQuote;
use Magento\Framework\App\ObjectManager;

class Quote extends Common
{
    public const CATEGORY = 'amasty_quote/quote/view';

    public function __construct(
        ArrayFilter\ScalarValueFilter $scalarValueFilter,
        ArrayFilter\KeyFilter $keyFilter,
        private readonly array $keysToCheck = [],
        private ?QuoteInitFlag $quoteInitFlag = null
    ) {
        parent::__construct($scalarValueFilter, $keyFilter);
        $this->quoteInitFlag ??= ObjectManager::getInstance()->get(QuoteInitFlag::class);
    }

    public function getLogMetadata(MetadataInterface $metadata): array
    {
        /** @var RequestQuote $quote */
        $quote = $metadata->getObject();

        return [
            LogEntry::ITEM => __('Request Quote #%1', $quote->getIncrementId()),
            LogEntry::CATEGORY => self::CATEGORY,
            LogEntry::CATEGORY_NAME => __('Request Quote'),
            LogEntry::ELEMENT_ID => $quote->getId(),
            LogEntry::PARAMETER_NAME => 'quote_id'
        ];
    }

    /**
     * @param RequestQuote $object
     * @return array
     */
    public function processBeforeSave($object): array
    {
        if ($this->quoteInitFlag->isQuoteInit()) {
            return [];
        }

        $quote = clone $object;
        $quote->load($object->getId());

        return $this->filterObjectData((array)$quote->getData());
    }

    /**
     * @param RequestQuote $object
     * @return array
     */
    public function processAfterSave($object): array
    {
        if ($this->quoteInitFlag->isQuoteInit()) {
            return [];
        }

        return parent::processAfterSave($object);
    }

    protected function filterObjectData(array $data): array
    {
        $data = parent::filterObjectData($data);

        return array_intersect_key($data, array_flip($this->keysToCheck));
    }
}
