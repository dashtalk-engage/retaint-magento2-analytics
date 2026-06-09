<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */
namespace Retaint\Analytics\Model\Types;
class OrderItems {
    public $orderItems = array();
    private $pageNum;
    protected $helper;
    protected $orderItemsFactory;
    protected $orderItemParserFactory;
    protected $eavConfig;
    protected $resource;
    protected $productMetadata;
    /**
     * @param \Retaint\Analytics\Helper\Data $helper
     * @param \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemsFactory
     * @param \Retaint\Analytics\Model\Types\OrderItemFactory $orderItemParserFactory
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     */
    public function __construct(
        \Retaint\Analytics\Helper\Data $helper,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemsFactory,
        \Retaint\Analytics\Model\Types\OrderItemFactory $orderItemParserFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata
    ) {
        $this->helper = $helper;
        $this->orderItemsFactory = $orderItemsFactory;
        $this->orderItemParserFactory = $orderItemParserFactory;
        $this->eavConfig = $eavConfig;
        $this->resource = $resource;
        $this->productMetadata = $productMetadata;
    }
    public function load($pageSize, $pageNum, $startDate = null, $endDate = null, $sortDir, $filterBy, $id, $customAttr, $ignoreCost)
    {
        $config = $this->helper->getConfig();
        $store = $this->helper->getStore();
        $edition = $this->productMetadata->getEdition();
        $this->pageNum = $pageNum;
        $attribute = $this->eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'cost');
        if ($id) {
            $collection = $this->orderItemsFactory->create()
                ->addAttributeToFilter('main_table.item_id', $id);
        } elseif ($startDate && $endDate) {
            $from = date('Y-m-d 00:00:00', strtotime($startDate));
            $to = date('Y-m-d 23:59:59', strtotime($endDate));
            $collection = $this->orderItemsFactory->create()
                ->addAttributeToFilter($filterBy, array('from' => $from, 'to' => $to));
        } else {
            $collection = $this->orderItemsFactory->create();
        }
        $collection->addAttributeToFilter('main_table.store_id', $this->helper->getStore()->getStoreId());
        if($ignoreCost) {
            $catProdEntDecTable = $this->resource->getTableName('catalog_product_entity_decimal');
            if($edition === 'Community') {
              $collection->getSelect()->joinLeft(
                  array('cost' => $catProdEntDecTable),
                  "main_table.product_id = cost.entity_id AND cost.attribute_id = {$attribute->getId()} AND cost.store_id = {$store->getStoreId()}",
                  array('cost' => 'value')
              );
            } else {
              $collection->getSelect()->joinLeft(
                  array('cost' => $catProdEntDecTable),
                  "main_table.product_id = cost.row_id AND cost.attribute_id = {$attribute->getId()} AND cost.store_id = {$store->getStoreId()}",
                  array('cost' => 'value')
              );
            }
        }
        $collection->setOrder('created_at', $sortDir);
        $collection->setCurPage($pageNum);
        $collection->setPageSize($pageSize);
        if ($collection->getLastPageNumber() < $pageNum) {
            return $this;
        }
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('sales_order_item');
        foreach ($collection as $orderItem) {
            $continue = true;
            $orderItemId = $orderItem->getId();
            if ($orderItem && $orderItemId) {
                if ($orderItem->getParentItemId()) {
                    foreach ($this->orderItems as $key => $oi) {
                        if ($orderItem->getParentItemId() == $this->orderItems[$key]->order_item_id) {
                            $this->orderItems[$key]->product_id = $orderItem->getProductId();
                            $continue = false;
                        }
                    }
                    if (!$continue) {
                        continue;
                    }
                }
                $model = $this->orderItemParserFactory->create()->parse($orderItem);
                if ($model) {
                    if ($customAttr) {
                        $sql = "SELECT project_name FROM " . $tableName . " WHERE item_id = " . $orderItemId;
                        $model->project_name = $connection->fetchOne($sql);
                    }
                    $this->orderItems[] = $model;
                }
            }
        }
        return $this->orderItems;
    }
}
