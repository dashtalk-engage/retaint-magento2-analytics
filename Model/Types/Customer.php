<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */
namespace Retaint\Analytics\Model\Types;

use Magento\Customer\Model\Group;
use Retaint\Analytics\Helper\Data;
use Retaint\Analytics\Model\Types\AddressFactory;

class Customer {

    protected $helper;
    protected $group;
    protected $addressFactory;

    /**
     * @param \Magento\Customer\Model\Group $group
     * @param \Retaint\Analytics\Helper\Data $helper
     * @param \Retaint\Analytics\Model\Types\AddressFactory $addressFactory
     */
    public function __construct(
        Group $group,
        Data $helper,
        AddressFactory $addressFactory
    ) {
        $this->group = $group;
        $this->helper = $helper;
        $this->addressFactory = $addressFactory;
    }

    public function parse($customer) {

        if (!$customer) {
            return $this;
        }

        $this->id = $customer->getId();
        $this->e_mail = $customer->getData('email');
        $groupId = $customer->getGroupId();
        $customerGroup = $this->group->load($groupId);
        $this->group_id = $groupId;
        $this->group_name = $customerGroup->getCode();
        $this->created_at = $customer->getCreatedAt();
        $this->updated_at = $customer->getUpdatedAt();
        $this->name = $customer->getName();
        $this->first_name = $customer->getFirstname();
        $this->last_name = $customer->getLastname();
        $this->gender = ((bool) $customer->getGender()) ? $customer->getGender() : '';
        $this->dob = ((bool) $customer->getDob()) ? $this->helper->formatDate($customer->getDob()) : '';
        $this->store = ((bool) $customer->getStore()->getCode()) ? $customer->getStore()->getCode() : '';
        $this->addresses = array();

        $addressParser = $this->addressFactory->create();
        if ($customer->getDefaultShippingAddress()) {
            $address = $addressParser->parse($customer->getDefaultShippingAddress());
            if ($address) {
                $this->addresses[] = $address;
            }
        }

        return $this;
    }
}
