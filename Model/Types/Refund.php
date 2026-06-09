<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */
namespace Retaint\Analytics\Model\Types;

class Refund {

    public function parse($refund) {
      
        $this->refund_id = $refund->getId();
        $this->order_id = $refund->getData('order_id');
        $this->amount = $refund->getData('grand_total');
        $this->created_at = $refund->getCreatedAt();
        $this->updated_at = $refund->getUpdatedAt();

        return $this;
    }
}
