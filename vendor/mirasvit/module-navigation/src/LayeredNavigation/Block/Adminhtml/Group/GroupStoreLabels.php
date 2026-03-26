<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\LayeredNavigation\Block\Adminhtml\Group;


use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Mirasvit\LayeredNavigation\Api\Data\GroupInterface;
use Magento\Backend\Block\Template;
use Mirasvit\LayeredNavigation\Repository\GroupRepository;

class GroupStoreLabels extends Template
{
    protected $_template = 'Mirasvit_LayeredNavigation::group.phtml';
    
    protected $groupRepository;

    public function __construct(
        GroupRepository $groupRepository,
        Template\Context $context
    ) {
        $this->groupRepository = $groupRepository;
        
        parent::__construct($context);
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface[]
     */
    public function getStores(): array
    {
        $admin = $this->_storeManager->getStore(0);

        $stores = array_merge([$admin], $this->_storeManager->getStores());

        return $stores;
    }

    public function getLabels(): array
    {
        $labels = [];
        $id     = (int)$this->getRequest()->getParam(GroupInterface::ID);

        if (!$id) {
            return $labels;
        }

        $group = $this->groupRepository->get($id);
        
        foreach ($group->getTitle() as $title) {
            $labels[$title['store_id']] = $title['label'];
        }
        
        return $labels;
    }
}
