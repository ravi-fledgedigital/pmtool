<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
declare(strict_types=1);

namespace OnitsukaTiger\OrderAttribute\Model\Entity\EntityData\Converter;

use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\EntityData\Converter\GetOptionLabels as GetLabelsResource;

class GetOptionLabels
{
    /**
     * @var GetLabelsResource
     */
    private $getLabelsResource;

    /**
     * @var array
     */
    private $cachedOptionLabels;

    public function __construct(GetLabelsResource $getLabelsResource)
    {
        $this->getLabelsResource = $getLabelsResource;
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function execute(): array
    {
        if ($this->cachedOptionLabels === null) {
            $this->cachedOptionLabels = $this->getLabelsResource->execute();
        }

        return $this->cachedOptionLabels;
    }
}
