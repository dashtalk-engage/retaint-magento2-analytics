<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */
namespace Retaint\Analytics\Model\Types;
class Order {
    protected $helper;
    protected $group;
    /**
     * @param \Magento\Customer\Model\Group $group
     * @param \Retaint\Analytics\Helper\Data $helper
     */
    public function __construct(
        \Magento\Customer\Model\Group $group,
        \Retaint\Analytics\Helper\Data $helper
    ) {
        $this->group = $group;
        $this->helper = $helper;
    }
    public function parse($order)
    {
        $this->id = $order->getId();
        $this->email = $order->getCustomerEmail();
        $this->increment_id = $order->getIncrementId();
        $this->customer_id = $order->getCustomerId() ? $order->getCustomerId() : 0;
        $this->customer_group_id = $order->getCustomerGroupId() ? $order->getCustomerGroupId() : null;
        $customerGroup = $this->group->load($order->getCustomerGroupId());
        $this->customer_group = $customerGroup->getCode();
        $this->created_at = $order->getCreatedAt();
        $this->updated_at = $order->getUpdatedAt();
        $this->state = $order->getState();
        $this->status = $order->getStatus();
        $this->customer_is_guest = $order->getCustomerIsGuest();
        $this->total_qty_ordered = (int) $order->getTotalQtyOrdered();
        $this->currency = $order->getOrderCurrencyCode();
        $this->base_currency_total = round($order->getBaseGrandTotal(), 2);
        $this->total = round($order->getGrandTotal(), 2);
        $this->tax = round($order->getTaxAmount(), 2);
        $this->shipping_total = round($order->getShippingAmount(), 2);
        $this->shipping_tax = round($order->getShippingTaxAmount(), 2);
        $this->shipping_description = $order->getShippingDescription();
        try {
            $payment = $order->getPayment();
            if ($payment) {
                $this->payment_method = $payment->getMethodInstance()->getTitle();
            } else {
                $this->payment_method = '';
            }
        } catch (\Exception $e) {
            $this->payment_method = '';
        }

        try {
            $this->discount_amount = round($order->getDiscountAmount(), 2);
            $this->discount_description = $order->getDiscountDescription();
            $this->discount_code = $order->getCouponCode();
        } catch (\Exception $e) {}

        $this->weight = $order->getWeight();
        $this->remote_ip = $order->getRemoteIp();
        $this->store = $order->getStore()->getCode();
        return $this;
    }
}
