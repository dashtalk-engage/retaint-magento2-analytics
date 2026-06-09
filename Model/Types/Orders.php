<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */
namespace Retaint\Analytics\Model\Types;
class Orders {
    public $orders = array();
    protected $helper;
    protected $orderCollection;
    protected $orderParserFactory;
    private $pageNum;
    /**
     * @param \Retaint\Analytics\Helper\Data $helper
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection
     * @param \Retaint\Analytics\Model\Types\OrderFactory $orderParserFactory
     */
    public function __construct(
        \Retaint\Analytics\Helper\Data $helper,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection,
        \Retaint\Analytics\Model\Types\OrderFactory $orderParserFactory
    ) {
        $this->helper = $helper;
        $this->orderCollection = $orderCollection;
        $this->orderParserFactory = $orderParserFactory;
    }
    public function load($pageSize, $pageNum, $startDate, $endDate, $sortDir, $filterBy, $id)
    {
        $config = $this->helper->getConfig();
        $this->pageNum = $pageNum;
        if ($id) {
            $collection = $this->orderCollection->create()
                ->addAttributeToFilter('main_table.increment_id', $id);
        } elseif ($startDate && $endDate) {
            $from = date('Y-m-d 00:00:00', strtotime($startDate));
            $to = date('Y-m-d 23:59:59', strtotime($endDate));
            $collection = $this->orderCollection->create()
                ->addAttributeToFilter($filterBy, array('from' => $from, 'to' => $to));
        } else {
            $collection = $this->orderCollection->create();
        }
        $collection->addAttributeToFilter('main_table.store_id', $this->helper->getStore()->getStoreId());
        $collection->addAttributeToSort('created_at', $sortDir);
        $collection->setCurPage($pageNum);
        $collection->setPageSize($pageSize);
        if ($collection->getLastPageNumber() < $pageNum) {
            return $this;
        }
        foreach ($collection as $order) {
            if ($order && $order->getId()) {
                $model = $this->orderParserFactory->create()->parse($order);
                if ($model) {
                    $this->orders[] = $model;
                }
            }
        }
        return $this->orders;
    }
}
