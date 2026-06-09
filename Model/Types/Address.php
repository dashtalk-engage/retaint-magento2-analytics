<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */
namespace Retaint\Analytics\Model\Types;

use Retaint\Analytics\Helper\Data;
use Magento\Directory\Model\Region;

class Address {

    const BILLING_ADDRESS_TYPE = 1;
    const SHIPPING_ADDRESS_TYPE = 2;
    public $address_id;
    public $address_type;
    public $firstname;
    public $lastname;
    public $e_mail;
    public $company;
    public $street;
    public $zip_code;
    public $city;
    public $state;
    public $country_id;
    public $telephone;
    public $fax;
    protected $helper;
    protected $region;

    /**
     * @param \Retaint\Analytics\Helper\Data $helper
     * @param \Magento\Directory\Model\Region $region
     */
    public function __construct(
        Data $helper,
        Region $region
    ) {
        $this->helper = $helper;
        $this->region = $region;
    }

    public function parse($address)
    {
        $this->address_id = $address['entity_id'];
        $this->address_type = $address['address_type'] == self::BILLING_ADDRESS_TYPE ? 'billing' : 'shipping';
        $this->firstname = $address['firstname'];
        $this->lastname = $address['lastname'];
        $this->e_mail = $address['email'];
        if (isset($address['company'])) {
            $this->company = $address['company'];
        }
        $this->street = $this->helper->toArray($address['street']);
        if ($this->street) {
            $this->street = implode(', ', $this->street);
        }
        $this->zip_code = $address['postcode'];
        $this->city = $address['city'];
        $region = $this->region->loadByName($address['region'], $address['country_id']);
        $this->state = $region->getData('code');
        $this->country_id = $address['country_id'];
        $this->telephone = $address['telephone'];
        $this->fax = $address['fax'];

        return $this;
    }
}
