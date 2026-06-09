<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */
namespace Retaint\Analytics\Model\Types;
class InventoryItem {
    protected $stockState;
    /**
     * @param \Magento\CatalogInventory\Api\StockStateInterface $stockState
     */
    public function __construct(
        \Magento\CatalogInventory\Api\StockStateInterface $stockState
    ) {
        $this->stockState = $stockState;
    }
    public function parse($product)
    {
        $this->product_id = $product->getId();
        $this->qty = $this->stockState->getStockQty($product->getId(), $product->getStore()->getWebsiteId());
        $this->price = $product->getPrice();
        $this->cost = $product->getCost();
        return $this;
    }
}
