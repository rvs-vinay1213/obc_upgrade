<?php

namespace Rvs\CustomerGroup\Model\ResourceModel;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\State\InvalidTransitionException;

class GroupRepository extends \Magento\Customer\Model\ResourceModel\GroupRepository
{
    public function save(\Magento\Customer\Api\Data\GroupInterface $group)
    {
        $this->_validate($group);

        /** @var \Magento\Customer\Model\Group $groupModel */
        $groupModel = null;
        if ($group->getId() || (string)$group->getId() === '0') {
            $this->_verifyTaxClassModel($group->getTaxClassId(), $group);
            $groupModel = $this->groupRegistry->retrieve($group->getId());
            $groupDataAttributes = $this->dataObjectProcessor->buildOutputDataArray(
                $group,
                \Rvs\CustomerGroup\Api\Data\GroupInterface::class
            );
            foreach ($groupDataAttributes as $attributeCode => $attributeData) {
                $groupModel->setDataUsingMethod($attributeCode, $attributeData);
            }
        } else {
            $groupModel = $this->groupFactory->create();
            $groupModel->setCode($group->getCode());

            $taxClassId = $group->getTaxClassId() ?: self::DEFAULT_TAX_CLASS_ID;
            $this->_verifyTaxClassModel($taxClassId, $group);
            $groupModel->setTaxClassId($taxClassId);
        }

        try {
            $this->groupResourceModel->save($groupModel);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            /**
             * Would like a better way to determine this error condition but
             *  difficult to do without imposing more database calls
             */
            if ($e->getMessage() == (string)__('Customer Group already exists.')) {
                throw new InvalidTransitionException(__('Customer Group already exists.'));
            }
            throw $e;
        }

        $this->groupRegistry->remove($groupModel->getId());

        $groupDataObject = $this->groupDataFactory->create()
            ->setId($groupModel->getId())
            ->setCode($groupModel->getCode())
            ->setTaxClassId($groupModel->getTaxClassId())
            ->setTaxClassName($groupModel->getTaxClassName());
        return $groupDataObject;
    }

    public function getById($id)
    {
        $groupModel = $this->groupRegistry->retrieve($id);
        $groupDataObject = $this->groupDataFactory->create()
            ->setId($groupModel->getId())
            ->setCode($groupModel->getCode())
            ->setTaxClassId($groupModel->getTaxClassId())
            ->setIgnoreMinQty($groupModel->getIgnoreMinQty())
            ->setTaxClassName($groupModel->getTaxClassName());
        return $groupDataObject;
    }

    private function _validate($group)
    {
        $exception = new InputException();
        if (!\Zend_Validate::is($group->getCode(), 'NotEmpty')) {
            $exception->addError(__('%fieldName is a required field.', ['fieldName' => 'code']));
        }

        if ($exception->wasErrorAdded()) {
            throw $exception;
        }
    }
}
