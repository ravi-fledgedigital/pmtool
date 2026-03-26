<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Block\Customer\Edit;

use Magento\Customer\ViewModel\Address\RegionProvider;
use Magento\Framework\App\Area;
use Magento\Framework\Registry as FrameworkRegistry;
use Magento\Framework\View\LayoutInterface;
use Magento\GiftRegistry\Block\Customer\Edit;
use Magento\GiftRegistry\Model\EntityFactory;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Translation\Test\Fixture\Translation as TranslationFixture;
use PHPUnit\Framework\TestCase;

#[
    AppArea(Area::AREA_FRONTEND)
]
class RegistryTest extends TestCase
{
    /**
     * @var Registry
     */
    private $block;

    /**
     * @var FrameworkRegistry
     */
    private $registry;

    /**
     * @var EntityFactory
     */
    private $entityFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $objectManager = Bootstrap::getObjectManager();
        $layout = $objectManager->get(LayoutInterface::class);
        $regionProvider = $objectManager->get(RegionProvider::class);
        $this->block = $layout->createBlock(
            Registry::class,
            'giftregistry_edit_registry',
            [
                'data' => [
                    'template' => 'Magento_GiftRegistry::edit/registry.phtml',
                    'region_provider' => $regionProvider
                ]
            ]
        );
        $layout->createBlock(
            Edit::class,
            'giftregistry_edit',
            [
                'data' => [],
            ]
        );
        $this->registry = $objectManager->get(FrameworkRegistry::class);
        $this->entityFactory = $objectManager->get(EntityFactory::class);
    }

    #[
        DataFixture(
            TranslationFixture::class,
            [
                'string' => 'Event Information',
                'translate' => 'Translated information',
                'locale' => 'en_US',
            ]
        ),
        DataFixture('Magento/Backend/controllers/_files/cache/empty_storage.php'),
    ]
    public function testHeaderTranslation() : void
    {
        $giftRegistry = $this->entityFactory->create();
        $giftRegistry->setTypeById(1);
        $this->registry->unregister('magento_giftregistry_entity');
        $this->registry->register('magento_giftregistry_entity', $giftRegistry);
        $html = $this->block->toHtml();
        $domXpath = new \DOMXPath($this->prepareDomDocument($html));
        $nodes = $domXpath->query('//fieldset[2]/legend/span');
        $this->assertEquals('Translated information', $nodes->item(0)->textContent);
    }

    /**
     * @param string $content
     * @return \DOMDocument
     */
    private function prepareDomDocument(string $content): \DOMDocument
    {
        $page =<<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
</head>
<body>
$content
</body>
</html>
HTML;
        $domDocument = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $domDocument->loadHTML($page);
        libxml_use_internal_errors(false);
        return $domDocument;
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->registry->unregister('magento_giftregistry_entity');
    }
}
