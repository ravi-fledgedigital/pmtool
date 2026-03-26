<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ImportJson\Model;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\ImportExport\Model\LocaleEmulatorInterface;
use Magento\ImportJsonApi\Api\Data\SourceDataInterface;
use Magento\ImportJsonApi\Api\StartImportInterface;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\AbstractSource;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\Import\Source\JsonFactory;
use Magento\ImportExport\Model\ImportFactory;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StartImport implements StartImportInterface
{
    /**
     * @param LocaleEmulatorInterface $localeEmulator
     * @param JsonFactory $jsonFactory
     * @param LoggerInterface $logger
     * @param ImportFactory $importFactory
     */
    public function __construct(
        private readonly LocaleEmulatorInterface $localeEmulator,
        private readonly JsonFactory $jsonFactory,
        private readonly LoggerInterface $logger,
        private readonly ImportFactory $importFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(SourceDataInterface $source): array
    {
        return $this->localeEmulator->emulate(
            function () use ($source) {
                return $this->performImport($source);
            },
            $source->getLocale() ?: null
        );
    }

    /**
     * Orchestrates the import process.
     *
     * @param SourceDataInterface $source
     * @return array
     * @throws LocalizedException
     */
    private function performImport(SourceDataInterface $source): array
    {
        $sourceAsArray = $this->getDataAsArray($source);
        /** @var Import $import */
        $import = $this->importFactory->create();
        $import->setData($sourceAsArray);

        $errors = $this->processSourceData($import, $source);
        if (!$errors) {
            $errors = $this->processImport($import);
        }

        return array_map(fn (mixed $error) => (string) $error, $errors);
    }

    /**
     * Converts the source data to an array for Import.
     *
     * @param SourceDataInterface $sourceData
     * @return array
     */
    private function getDataAsArray(SourceDataInterface $sourceData): array
    {
        return [
            'entity' => $sourceData->getEntity(),
            'behavior' => $sourceData->getBehavior(),
            Import::FIELD_NAME_VALIDATION_STRATEGY => $sourceData->getValidationStrategy(),
            Import::FIELD_NAME_ALLOWED_ERROR_COUNT => $sourceData->getAllowedErrorCount(),
            'locale' => $sourceData->getLocale(),
            Import::FIELD_NAME_IMG_FILE_DIR => $sourceData->getImportImagesFileDir() ?? null,
        ];
    }

    /**
     * Handles the validation of source data.
     *
     * @param Import $import
     * @param SourceDataInterface $source
     * @return array
     */
    private function processSourceData(Import $import, SourceDataInterface $source): array
    {
        $errors = [];
        try {
            $importAdapter = $this->createImportAdapter($source->getItems());
            $errors = $this->validate($import, $importAdapter);
        } catch (LocalizedException $e) {
            $errors[] = $e->getMessage();
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $errors[] = __('Sorry, but the data is invalid or the file is not uploaded.');
        }

        return $errors;
    }

    /**
     * Stores data in memory or temp file, and loads JSON adapter.
     *
     * @param array $importData
     * @return AbstractSource
     * @throws FileSystemException
     */
    private function createImportAdapter(array $importData): AbstractSource
    {
        $parameters = [
            'items' => $importData
        ];

        return $this->jsonFactory->create($parameters);
    }

    /**
     * Validates the import and collects any errors.
     *
     * @param Import $import
     * @param AbstractSource $source
     * @return array
     * @throws LocalizedException
     */
    private function validate(Import $import, AbstractSource $source): array
    {
        $errors = [];
        $validationResult = $import->validateSource($source);
        $errorAggregator = $import->getErrorAggregator();

        if (!$import->getProcessedRowsCount() && !$errorAggregator->getErrorsCount()) {
            return [__('This file is empty. Please try another one.')];
        }

        if ($validationResult && $import->getProcessedRowsCount() && !$import->isImportAllowed()) {
            return [__('The file is valid, but we can\'t import it for some reason.')];
        }

        if ($errorAggregator->getErrorsCount() && !$this->canSkipValidationErrors($import)) {
            foreach ($errorAggregator->getAllErrors() as $error) {
                $errors[] = __('Row %1: %2', $error->getRowNumber() + 1, $error->getErrorMessage());
            }
        }

        return $errors;
    }

    /**
     * Checks whether validation errors can be skipped.
     *
     * @param Import $import
     * @return bool
     */
    private function canSkipValidationErrors(Import $import): bool
    {
        $validationStrategy = $import->getData(Import::FIELD_NAME_VALIDATION_STRATEGY);

        return $validationStrategy === ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_SKIP_ERRORS
            // At least one row is valid
            && $import->getValidatedIds();
    }

    /**
     * Processes import after validation.
     *
     * @param Import $import
     * @return array
     * @throws LocalizedException
     */
    private function processImport(Import $import): array
    {
        $errors = [];
        $processedEntities = $import->getProcessedEntitiesCount();
        $errorAggregator = $import->getErrorAggregator();
        $errorAggregator->initValidationStrategy(
            $import->getData(Import::FIELD_NAME_VALIDATION_STRATEGY),
            $import->getData(Import::FIELD_NAME_ALLOWED_ERROR_COUNT)
        );
        $errorAggregator->clear();

        try {
            $import->importSource();
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        }

        if ($import->getErrorAggregator()->hasToBeTerminated()) {
            $errors[] = __('Maximum error count has been reached or system error is occurred!');
        } else {
            $import->invalidateIndex();
        }

        if (!$errors) {
            $errors = [__('Entities Processed: %1', $processedEntities)];
        }

        return $errors;
    }
}
