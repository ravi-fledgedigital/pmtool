<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Block\Adminhtml;

use Amasty\Rma\Model\OptionSource\Grid;
use Magento\Backend\Block\Template;

class MenuRequestCounter extends Template
{
    /**
     * @var \Amasty\Rma\Model\Status\ResourceModel\Status
     */
    private $statusResource;

    /**
     * @var \Amasty\Rma\Model\Request\ResourceModel\Request
     */
    private $requestResource;

    public function __construct(
        Template\Context $context,
        \Amasty\Rma\Model\Status\ResourceModel\Status $statusResource,
        \Amasty\Rma\Model\Request\ResourceModel\Request $requestResource,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->statusResource = $statusResource;
        $this->requestResource = $requestResource;
    }

    /**
     * @return int
     */
    public function getManageCount()
    {
        return $this->getRequestCountByGrid(Grid::MANAGE);
    }

    /**
     * @return int
     */
    public function getPendingCount()
    {
        return $this->getRequestCountByGrid(Grid::PENDING);
    }

    /**
     * @param int $grid
     *
     * @return int
     */
    public function getRequestCountByGrid($grid)
    {
        return $this->requestResource->getRequestCountByStatuses(
            $this->statusResource->getGridStatuses((int)$grid)
        );
    }
}
