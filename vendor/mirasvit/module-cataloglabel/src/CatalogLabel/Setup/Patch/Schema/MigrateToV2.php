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
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\CatalogLabel\Setup\Patch\Schema;


use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Mirasvit\CatalogLabel\Api\Data\DisplayInterface;
use Mirasvit\CatalogLabel\Api\Data\LabelInterface;
use Mirasvit\CatalogLabel\Api\Data\PlaceholderInterface;
use Mirasvit\CatalogLabel\Model\System\Config\Source\PositionSource;
use Mirasvit\CatalogLabel\Repository\TemplateRepository;
use Mirasvit\Core\Service\YamlService;

class MigrateToV2 implements DataPatchInterface, PatchVersionInterface
{
    private $setup;

    private $positionSource;

    private $filter;

    private $yamlService;

    private $templateRepository;

    private $oldLabels = [];

    private $oldPlaceholders = [];

    private $newLabels = [];

    private $newDisplays = [];

    private $newPlaceholdersByPosition = [];

    public function __construct(
        ModuleDataSetupInterface $setup,
        PositionSource $positionSource,
        FilterManager $filter,
        YamlService $yamlService,
        TemplateRepository $templateRepository
    ) {
        $this->setup              = $setup;
        $this->positionSource     = $positionSource;
        $this->filter             = $filter;
        $this->yamlService        = $yamlService;
        $this->templateRepository = $templateRepository;
    }

    public static function getDependencies()
    {
        return [];
    }

    public static function getVersion()
    {
        return '1.0.7';
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $setup = $this->setup;

        $setup->getConnection()->startSetup();

        $existLabels = $setup->getConnection()
            ->query('SELECT label_id FROM ' . $setup->getTable('mst_cataloglabel_label'))
            ->fetchAll();

        if (count($existLabels)) {
            $this->pregeneratePlaceholders();
            $this->retrieveOldLabels();
            $this->prepareData();
            $this->writeAndCleanup();
        }

        $this->installTemplates();

        $setup->getConnection()->endSetup();
    }

    private function pregeneratePlaceholders()
    {
        $placeholderData = [];

        $keys = [
            PlaceholderInterface::NAME,
            PlaceholderInterface::CODE,
            PlaceholderInterface::POSITION
        ];

        foreach ($this->getPositionsArray() as $code => $label) {
            $placeholderData[] = [
                $label,
                $this->filter->translitUrl($label),
                $code
            ];
        }

        $this->setup->getConnection()->insertArray(
            $this->setup->getTable(PlaceholderInterface::TABLE_NAME),
            $keys,
            $placeholderData
        );

        $placeholders = $this->setup->getConnection()
            ->query('SELECT * FROM ' . $this->setup->getTable(PlaceholderInterface::TABLE_NAME))
            ->fetchAll();

        foreach ($placeholders as $row) {
            $this->newPlaceholdersByPosition[$row[PlaceholderInterface::POSITION]] = $row;
        }
    }

    private function prepareData()
    {
        $processedAttributeLabels = [];

        foreach (['rule', 'attribute'] as $labelType) {
            $select = $this->setup->getConnection()
                ->select()
                ->from([$labelType => $this->setup->getTable('mst_cataloglabel_label_' . $labelType)])
                ->joinLeft(
                    ['display' => $this->setup->getTable('mst_cataloglabel_label_display')],
                    $labelType . '.display_id = display.display_id'
                )->order($labelType . '_id DESC');

            $data = $select->query()->fetchAll();

            foreach ($data as $key => $value) {
                if (isset($value['option_id']) && $value['option_id']) {
                    $k = $value['option_id'] . '_' . $value['label_id'];

                    // to not process old attribute labels multiple times
                    if (in_array($k, $processedAttributeLabels)) {
                        unset($data[$key]);
                        continue;
                    }

                    $processedAttributeLabels[] = $k;
                }

                $data[$key][DisplayInterface::ATTR_OPTION_ID] = isset($value['option_id'])
                    ? $value['option_id']
                    : null;
            }

            $this->prepareDataForMigration($data);
        }
    }

    private function convertConditions(string $conditions): string
    {
        if (!json_decode($conditions)) {
            return \Mirasvit\Core\Service\SerializeService::encode(\Magento\Framework\Serialize\Serializer\Json::unserialize($conditions));
        }

        return $conditions;
    }

    private function prepareDataForMigration(array $data)
    {
        foreach ($data as $row) {
            $labelId        = $row['label_id'];
            $oldLabelData   = $this->oldLabels[$labelId];
            $newLabelData   = [];
            $newDisplayData = [];

            if (!isset($this->newLabels[$labelId])) {
                foreach ($this->getLabelFields() as $field) {
                    if ($field == LabelInterface::CONDITIONS_SERIALIZED) {
                        $newLabelData[] = isset($row[$field])
                            ? $this->convertConditions($row[$field])
                            : null;

                        continue;
                    }

                    $newLabelData[] = isset($oldLabelData[$field]) && $oldLabelData[$field]
                        ? $oldLabelData[$field]
                        : null;
                }

                $this->newLabels[$labelId] = $newLabelData;
            }

            foreach (['list', 'view'] as $type) {
                foreach ($row as $key => $value) {
                    if (strpos($key, $type) !== 0) {
                        continue;
                    }

                    $newKey = str_replace($type . '_', '', $key);

                    if ($newKey == 'image') {
                        $newKey .= '_path';
                    }

                    $newDisplayData[$newKey] = $value;
                }

                $newDisplayData[DisplayInterface::TYPE]           = $type;
                $newDisplayData[DisplayInterface::LABEL_ID]       = $labelId;
                $newDisplayData[DisplayInterface::ATTR_OPTION_ID] = $row[DisplayInterface::ATTR_OPTION_ID];
                $newDisplayData[DisplayInterface::PLACEHOLDER_ID] = $this->ensurePlaceholder(
                    (int)$oldLabelData['placeholder_id'],
                    $type,
                    $newDisplayData['position'] ?: null
                );

                $this->newDisplays[] = $this->ensureDisplayData($newDisplayData);
            }
        }
    }

    // fill display data by keys in order to insert
    private function ensureDisplayData(array $displayData): array
    {
        $data = [];

        foreach ($this->getDisplayFields() as $field) {
            $data[$field] = isset($displayData[$field]) ? $displayData[$field] : null;
        }

        return $data;
    }

    // returns preconfigured placeholder ID or creates new MANUAL placeholder and returns its ID
    private function ensurePlaceholder(int $placeholderId, string $type, ?string $position = null): int
    {
        if (!count($this->oldPlaceholders)) {
            $this->retrieveOldPlaceholders();
        }

        if (!$this->oldPlaceholders[$placeholderId]['is_auto_for_' . $type]) {
            return (int)$this->newPlaceholdersByPosition['MANUAL'][PlaceholderInterface::ID];
        } else {
            return $position
                ? (int)$this->newPlaceholdersByPosition[$position][PlaceholderInterface::ID]
                : (int)$this->newPlaceholdersByPosition['TL'][PlaceholderInterface::ID];
        }
    }

    private function retrieveOldLabels()
    {
        $oldLabels = $this->getDataFromTable('mst_cataloglabel_label');

        foreach ($oldLabels as $data) {
            $this->oldLabels[$data['label_id']] = $data;
        }
    }

    private function retrieveOldPlaceholders()
    {
        $old = $this->getDataFromTable('mst_cataloglabel_placeholder');

        foreach ($old as $data) {
            $this->oldPlaceholders[$data['placeholder_id']] = $data;
        }
    }

    private function getDataFromTable(string $tableName, array $filters = []): array
    {
        $query = 'SELECT * FROM ' . $this->setup->getTable($tableName);

        if (count($filters)) {
            $query .= ' WHERE ' . implode(' AND ', $filters);
        }

        $data = $this->setup->getConnection()->query($query)->fetchAll();

        return $data;
    }

    private function getLabelFields(): array
    {
        return [
            LabelInterface::ID,
            LabelInterface::TYPE,
            LabelInterface::ATTRIBUTE_ID,
            LabelInterface::NAME,
            LabelInterface::IS_ACTIVE,
            LabelInterface::ACTIVE_FROM,
            LabelInterface::ACTIVE_TO,
            LabelInterface::SORT_ORDER,
            LabelInterface::STORE_IDS,
            LabelInterface::CUSTOMER_GROUP_IDS,
            LabelInterface::CONDITIONS_SERIALIZED,
            LabelInterface::CREATED_AT,
            LabelInterface::UPDATED_AT
        ];
    }

    private function getDisplayFields(): array
    {
        return [
            DisplayInterface::TYPE,
            DisplayInterface::LABEL_ID,
            DisplayInterface::PLACEHOLDER_ID,
            DisplayInterface::TEMPLATE_ID,
            DisplayInterface::ATTR_OPTION_ID,
            DisplayInterface::TITLE,
            DisplayInterface::DESCRIPTION,
            DisplayInterface::IMAGE_PATH,
            DisplayInterface::URL,
            DisplayInterface::STYLE
        ];
    }

    private function getPositionsArray(): array
    {
        $positions = $this->positionSource->getPositionsArray();

        $positions['MANUAL'] = 'Manual position';

        return $positions;
    }

    private function writeAndCleanup() {
        $setup = $this->setup;

        $setup->getConnection()->insertArray(
            $setup->getTable(LabelInterface::TABLE_NAME),
            $this->getLabelFields(),
            array_values($this->newLabels)
        );

        $setup->getConnection()->insertArray(
            $setup->getTable(DisplayInterface::TABLE_NAME),
            $this->getDisplayFields(),
            array_values($this->newDisplays)
        );

        $placeholderIds = [];

        $used = $setup->getConnection()
            ->query('SELECT DISTINCT placeholder_id FROM ' . $setup->getTable(DisplayInterface::TABLE_NAME))
            ->fetchAll();

        foreach ($used as $row) {
            $placeholderIds[] = $row['placeholder_id'];
        }

        if (count($placeholderIds)) {
            $setup->getConnection()->query(
                'DELETE FROM ' . $setup->getTable(PlaceholderInterface::TABLE_NAME)
                . ' WHERE placeholder_id NOT IN(' . implode(',', $placeholderIds) . ')'
            );
        }
    }

    public function installTemplates()
    {
        $data = $this->yamlService->loadFile(
            file_get_contents(dirname(__FILE__) . '/../../data/templates.yaml')
        );

        foreach ($data as $templateData) {
            $template = $this->templateRepository->create();
            $template->setData($templateData);

            $this->templateRepository->save($template);
        }
    }
}
