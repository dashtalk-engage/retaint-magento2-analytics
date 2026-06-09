<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */
namespace Retaint\Analytics\Model\Types;

class RefundItem {

    public function parse($refund) {
      
        $this->refund_item_id = $refund->getId();
        $this->parent_id = $refund->getData('parent_id');
        $this->product_id = $refund->getData('product_id');
        $this->order_item_id = $refund->getData('order_item_id');
        $this->qty = $refund->getData('qty');
        $this->row_total = $refund->getData('row_total');
        $this->tax_amount = $refund->getData('tax_amount');
        $this->price = $refund->getData('base_price');
        $this->created_at = $refund->getCreatedAt();
        $this->updated_at = $refund->getUpdatedAt();

        return $this;
    }
}
