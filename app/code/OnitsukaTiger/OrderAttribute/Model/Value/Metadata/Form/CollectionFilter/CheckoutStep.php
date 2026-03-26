<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
declare(strict_types=1);

namespace OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form\CollectionFilter;

use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Collection;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;

class CheckoutStep implements FilterInterface
{
    /**
     * @var State
     */
    private $appState;

    public function __construct(State $appState)
    {
        $this->appState = $appState;
    }

    public function apply(Collection $collection): void
    {
        if ($this->appState->getAreaCode() !== Area::AREA_ADMINHTML) {
            $collection->addFilterUnassignedOnCheckout();
        }
    }
}
