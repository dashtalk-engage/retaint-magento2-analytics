<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */
namespace Retaint\Analytics\Model\Types;

class ProductAlert {

    public function parse($alert) {

        $this->id = $alert->getAlertStockId();
        $this->customer_id = $alert->getCustomerId();
        $this->product_id = $alert->getProductId();
        $this->created_at = $alert->getAddDate();

        return $this;
        
    }
}
