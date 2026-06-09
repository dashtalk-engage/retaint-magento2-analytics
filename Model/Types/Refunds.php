<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */
namespace Retaint\Analytics\Model\Types;

use Retaint\Analytics\Helper\Data;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory;
use Retaint\Analytics\Model\Types\RefundFactory;

class Refunds {

    public $refunds = array();
    protected $helper;
    protected $refundsCollection;
    protected $refundFactory;
    private $pageNum;

    /**
     * @param \Retaint\Analytics\Helper\Data $helper
     * @param \Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory $refundsCollection
     * @param \Retaint\Analytics\Model\Types\RefundFactory $refundFactory
     */
    public function __construct(
        \Retaint\Analytics\Helper\Data $helper,
        \Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory $refundsCollection,
        \Retaint\Analytics\Model\Types\RefundFactory $refundFactory
    ) {
        $this->helper = $helper;
        $this->refundsCollection = $refundsCollection;
        $this->refundFactory = $refundFactory;
    }

    public function load($pageSize, $pageNum, $startDate, $endDate, $sortDir, $filterBy, $id)
    {
        $config = $this->helper->getConfig();
        $this->pageNum = $pageNum;

        if ($id) {
            $refunds = $this->refundsCollection->create()
                ->addAttributeToFilter('entity_id', $id);
        } elseif ($startDate && $endDate) {
            $from = date('Y-m-d 00:00:00', strtotime($startDate));
            $to = date('Y-m-d 23:59:59', strtotime($endDate));

            $refunds = $this->refundsCollection->create()
                ->addAttributeToFilter($filterBy, array('from' => $from, 'to' => $to));
        } else {
            $refunds = $this->refundsCollection->create();
        }
        $refunds->addAttributeToFilter('main_table.store_id', $this->helper->getStore()->getStoreId());
        $refunds->setOrder('created_at', $sortDir);
        $refunds->setCurPage($pageNum);
        $refunds->setPageSize($pageSize);

        if ($refunds->getLastPageNumber() < $pageNum) {
            return $this;
        }

        foreach ($refunds as $refund) {
            $retaintRefund = $this->refundFactory->create();
            $model = $retaintRefund->parse($refund);
            if ($model) {
                $this->refunds[] = $model;
            }
        }

        return $this->refunds;
    }
}
