<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */
namespace Retaint\Analytics\Model\Types;
class Inventory {
    public $inventory = array();
    private $pageNum;
    protected $helper;
    protected $productFactory;
    protected $inventoryItemFactory;
    protected $catalogConfig;
    /**
     * @param \Retaint\Analytics\Helper\Data $helper
     * @param \Retaint\Analytics\Model\Types\InventoryItemFactory $inventoryItemFactory
     * @param \Magento\Catalog\Model\Config $catalogConfig
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productFactory
     */
    public function __construct(
        \Retaint\Analytics\Helper\Data $helper,
        \Retaint\Analytics\Model\Types\InventoryItemFactory $inventoryItemFactory,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productFactory
    ) {
        $this->helper = $helper;
        $this->productFactory = $productFactory;
        $this->inventoryItemFactory = $inventoryItemFactory;
        $this->catalogConfig = $catalogConfig;
    }
    public function load($pageSize, $pageNum, $sortDir, $filterBy, $id)
    {
        $config = $this->helper->getConfig();
        $this->pageNum = $pageNum;
        if($id) {
            $inventory = $this->productFactory->create()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', $id);
        } else {
            $inventory = $this->productFactory->create()
                ->addAttributeToSelect('*');
        }
        $inventory->setStore($this->helper->getStore());
        $inventory->setOrder('entity_id', $sortDir);
        $inventory->setCurPage($pageNum);
        $inventory->setPageSize($pageSize);
        if ($inventory->getLastPageNumber() < $pageNum) {
            return $this;
        }
        foreach ($inventory as $product) {
            $productParser = $this->inventoryItemFactory->create();
            $model = $productParser->parse($product);
            if ($model) {
                $this->inventory[] = $model;
            }
        }
        return $this->inventory;
    }
}
