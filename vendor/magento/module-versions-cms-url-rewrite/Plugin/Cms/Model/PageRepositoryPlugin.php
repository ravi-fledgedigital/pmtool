<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCmsUrlRewrite\Plugin\Cms\Model;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Model\PageRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewrite as UrlRewriteResourceModel;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Model\UrlRewriteFactory;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\VersionsCms\Helper\Hierarchy;
use Magento\VersionsCms\Model\Hierarchy\Node;
use Magento\VersionsCms\Model\Hierarchy\NodeFactory;

/**
 * Generate and delete url rewrites for root hierarchy of the page
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PageRepositoryPlugin
{
    /**
     * @var UrlPersistInterface
     */
    private $urlPersist;

    /**
     * @var Hierarchy
     */
    private $cmsHierarchy;

    /**
     * @var NodeFactory
     */
    private $hierarchyNodeFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var UrlFinderInterface
     */
    private $urlFinder;

    /**
     * @var UrlRewriteFactory
     */
    private $urlRewriteFactory;

    /**
     * @var UrlRewriteResourceModel
     */
    private UrlRewriteResourceModel $urlRewriteResourceModel;

    /**
     * @param UrlPersistInterface $urlPersist
     * @param Hierarchy $cmsHierarchy
     * @param NodeFactory $hierarchyNodeFactory
     * @param StoreManagerInterface $storeManager
     * @param UrlFinderInterface $urlFinder
     * @param UrlRewriteFactory $urlRewriteFactory
     * @param UrlRewriteResourceModel $urlRewriteResourceModel
     */
    public function __construct(
        UrlPersistInterface $urlPersist,
        Hierarchy $cmsHierarchy,
        NodeFactory $hierarchyNodeFactory,
        StoreManagerInterface $storeManager,
        UrlFinderInterface $urlFinder,
        UrlRewriteFactory $urlRewriteFactory,
        UrlRewriteResourceModel $urlRewriteResourceModel,
    ) {
        $this->urlPersist = $urlPersist;
        $this->cmsHierarchy = $cmsHierarchy;
        $this->hierarchyNodeFactory = $hierarchyNodeFactory;
        $this->storeManager = $storeManager;
        $this->urlFinder = $urlFinder;
        $this->urlRewriteFactory = $urlRewriteFactory;
        $this->urlRewriteResourceModel = $urlRewriteResourceModel;
    }

    /**
     * Flag to generate url rewrites for the page if root hierarchy was selected
     *
     * @param PageRepository $subject
     * @param PageInterface $page
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        PageRepository $subject,
        PageInterface $page
    ) {
        if (!$this->cmsHierarchy->isEnabled()) {
            return;
        }
        if ($page->dataHasChangedFor('assign_to_root')
            && $page->getData('assign_to_root') === true
        ) {
            $page->setData('rewrites_update_force', true);
        }
    }

    /**
     * Update url rewrites if root hierarchy is unselected for the page
     *
     * @param PageRepository $subject
     * @param PageInterface $page
     * @return PageInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws NoSuchEntityException
     * @throws \Exception
     */
    public function afterSave(
        PageRepository $subject,
        PageInterface $page
    ) {
        if (!$this->cmsHierarchy->isEnabled()) {
            return $page;
        }
        if ($page->hasData('website_root') && !$page->getData('website_root')) {
            $nodeUrl = null;
            $node = $this->hierarchyNodeFactory->create(
                [
                    'data' => [
                        'scope' => Node::NODE_SCOPE_STORE,
                        'scope_id' => $this->storeManager->getStore()->getId(),
                    ],
                ]
            )->getHeritage();
            $requestUrl = $page->getIdentifier();
            if ($node->checkIdentifier($requestUrl, $this->storeManager->getStore())) {
                if (!$node->getId()) {
                    $collection = $node->getNodesCollection();
                    foreach ($collection as $item) {
                        if ($item->getPageIdentifier() == $requestUrl) {
                            $nodeUrl = $item->getRequestUrl();
                            break;
                        }
                    }
                }
            }
            $this->setNodeTargetPathUrlRewrite($nodeUrl, [UrlRewrite::REQUEST_PATH => $requestUrl]);
        }
        return $page;
    }

    /**
     * Update url rewrites with 301 redirect and node target path
     *
     * @param string|null $nodeUrl
     * @param array $filterData
     * @return void
     * @throws \Exception
     */
    private function setNodeTargetPathUrlRewrite(?string $nodeUrl, array $filterData): void
    {
        if (!empty($nodeUrl) && !empty($filterData)) {
            $findRewrite = $this->urlFinder->findOneByData($filterData);
            if (!empty($findRewrite) && $findRewrite->getTargetPath()!==$nodeUrl) {
                $urlRewrite = $this->urlRewriteFactory->create();
                $this->urlRewriteResourceModel->load($urlRewrite, $findRewrite->getUrlRewriteId());
                if ($urlRewrite->getId()) {
                    $urlRewrite->setTargetPath($nodeUrl);
                    $urlRewrite->setRedirectType(301);
                    $urlRewrite->save();
                }
            }
        }
    }
}
