<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
declare(strict_types=1);

namespace OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form\CollectionFilter;

use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Attribute\Collection;

interface FilterInterface
{
    /**
     * @param Collection $collection
     * @throws \Exception
     * @return void
     */
    public function apply(Collection $collection): void;
}
