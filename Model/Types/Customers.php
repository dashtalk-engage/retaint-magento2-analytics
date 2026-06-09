<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */
namespace Retaint\Analytics\Model\Types;
class Customers {
    public $customers = array();
    private $pageNum;
    protected $helper;
    protected $customerFactory;
    protected $customerParserFactory;
    /**
     * @param \Retaint\Analytics\Helper\Data $helper
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerFactory
     * @param \Retaint\Analytics\Model\Types\CustomerFactory $customerParserFactory
     */
    public function __construct(
        \Retaint\Analytics\Helper\Data $helper,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerFactory,
        \Retaint\Analytics\Model\Types\CustomerFactory $customerParserFactory
    ) {
        $this->helper = $helper;
        $this->customerFactory = $customerFactory;
        $this->customerParserFactory = $customerParserFactory;
    }
    public function load($pageSize, $pageNum, $startDate, $endDate, $sortDir, $filterBy, $id) {
        $config = $this->helper->getConfig();
        $this->pageNum = $pageNum;
        if ($id) {
            $collection = $this->customerFactory->create()
                ->addAttributeToFilter('entity_id', $id);
        } elseif ($startDate && $endDate) {
            $from = date('Y-m-d 00:00:00', strtotime($startDate));
            $to = date('Y-m-d 23:59:59', strtotime($endDate));
            $collection = $this->customerFactory->create()
                ->addAttributeToFilter($filterBy, array('from' => $from, 'to' => $to));
        } else {
            $collection = $this->customerFactory->create();
        }
        $collection->addAttributeToFilter('store_id', $this->helper->getStore()->getStoreId());
        $collection->setOrder('created_at', $sortDir);
        $collection->setCurPage($pageNum);
        $collection->setPageSize($pageSize);
        if ($collection->getLastPageNumber() < $pageNum) {
            return $this;
        }
        
        foreach ($collection as $customer) {
            $customerId = $customer->getId();
            if ($customer && $customerId) {
                $model = $this->customerParserFactory->create()->parse($customer);
                if ($model) {
                    $this->customers[] = $model;
                }
            }
        }
        return $this->customers;
    }
}
