<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftRegistry\Controller\Index;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\GiftRegistry\Model\Entity as GiftRegistry;

/**
 * Process gift registry edit action
 *
 * @SuppressWarnings(PHPMD.AllPurposeAction)
 */
class EditPost extends \Magento\GiftRegistry\Controller\Index implements HttpPostActionInterface
{
    /**
     * Strip tags from received data
     *
     * @param string|array $data
     * @return string|array
     */
    protected function _filterPost($data)
    {
        if (!is_array($data)) {
            return strip_tags((string)$data);
        }
        foreach ($data as &$field) {
            if (!empty($field)) {
                if (!is_array($field)) {
                    $field = strip_tags((string)$field);
                } else {
                    $field = $this->_filterPost($field);
                }
            }
        }
        return $data;
    }

    /**
     * Create gift registry action
     *
     * @return ResponseInterface
     */
    public function execute(): ResponseInterface
    {
        $request = $this->getRequest();
        if (!($typeId = $request->getParam('type_id'))) {
            return $this->_redirect('*/*/addselect');
        }
        if (!$this->_formKeyValidator->validate($request)) {
            return $this->_redirect('*/*/edit', ['type_id', $typeId]);
        }
        $isError = false;
        if ($request->isPost() && ($data = $request->getPostValue())) {
            try {
                $model = $this->initGiftRegistryEntity();
                $data = $this->_filterPost($this->dataHelper->filterDatesByFormat($data, $model->getDateFieldArray()));
                $request->setPostValue($data);
                $model->importData($data, $model->getEntityId() == 0);
                $this->addErrors($model->validate());
                $this->saveGiftRegistryData($model, $data);
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $isError = true;
            } catch (\Exception $e) {
                $this->messageManager->addError(__("We couldn't save this gift registry."));
                $this->logger->critical($e, ['exception' => $e]);
                $isError = true;
            }

            if ($isError) {
                $this->_getSession()->setGiftRegistryEntityFormData($request->getPostValue());
                $entityId = $request->getParam('entity_id');
                $params = $entityId ? ['entity_id' => $entityId] : ['type_id' => $typeId];

                return $this->_redirect('*/*/edit', $params);
            }
        }

        return $this->_redirect('*/*/');
    }

    /**
     * Save gift registry details with registrants and address
     *
     * @param GiftRegistry $model
     * @param array $data
     * @return void
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Validator\ValidateException
     */
    private function saveGiftRegistryData(GiftRegistry $model, array $data): void
    {
        if (empty($this->messageManager->getMessages()->getErrors())) {
            $this->giftRegistryAddress->execute($model, $this->mapAddressInfo($data));
            $model->save();
            $this->registrantsUpdater->execute($this->getRegistrantsFromRequest(), (int)$model->getId());

            if (!$this->getRequest()->getParam('entity_id')) {
                $model->sendNewRegistryEmail();
            } else {
                $this->removeRegistrantOrphans($model);
            }
            $this->messageManager->addSuccess(__('You saved this gift registry.'));
        }
    }

    /**
     * Initialize entity model with basic request data
     *
     * @throws LocalizedException
     */
    private function initGiftRegistryEntity(): GiftRegistry
    {
        $entityId = (int)$this->getRequest()->getParam('entity_id');
        if ($entityId) {
            $model = $this->_initEntity('entity_id');
        } else {
            $model = $this->giftRegistryFactory->create();
            if ($model->setTypeById($this->getRequest()->getParam('type_id')) === false) {
                throw new LocalizedException(__('The type is incorrect. Verify and try again.'));
            }
        }

        return $model;
    }

    /**
     * Extracts registrants data from request
     *
     * @return array
     */
    private function getRegistrantsFromRequest(): array
    {
        $registrantsPost = $this->getRequest()->getPost('registrant');
        if (!is_array($registrantsPost)) {
            return [];
        }

        array_walk($registrantsPost, function (&$value) {
            if (!empty($value['person_id'])) {
                $value['id'] = $value['person_id'];
            }
        });

        return $registrantsPost;
    }

    /**
     * Cleans registrants list of potential orphans
     *
     * @param GiftRegistry $model
     * @return void
     */
    private function removeRegistrantOrphans(GiftRegistry $model): void
    {
        $registrants = $model->getRegistrantsCollection();
        $postRegistrantEmails = array_column($this->getRegistrantsFromRequest(), 'email');
        $personLeft = array_filter(
            $registrants->toArray()['items'],
            function ($registrant) use ($postRegistrantEmails) {
                return in_array($registrant['email'], $postRegistrantEmails);
            }
        );

        $registrants->getIterator()->current()->getResource()->deleteOrphan(
            $model->getId(),
            array_column($personLeft, 'person_id')
        );
    }

    /**
     * Prepares address data for save
     *
     * @param array $data
     * @return array
     */
    private function mapAddressInfo(array $data): array
    {
        $addressData['address_data'] = $data['address'];
        $addressData['address_data']['region'] = [
            'region_id' => $data['address']['region_id'],
            'region' => $data['address']['region']
        ];
        if (isset($data['address_type_or_id']) && is_numeric($data['address_type_or_id'])) {
            $addressData['address_id'] = $data['address_type_or_id'];
        }

        return $addressData;
    }

    /**
     * Adds multiple error messages to message manager
     *
     * @param mixed $errors
     * @return void
     */
    private function addErrors(mixed $errors): void
    {
        if (empty($errors) || true === $errors) {
            return;
        }
        foreach ($errors as $err) {
            $this->messageManager->addError($err);
        }
    }
}
