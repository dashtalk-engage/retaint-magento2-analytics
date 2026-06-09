<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */
namespace Retaint\Analytics\Model\Types;

use Retaint\Analytics\Helper\Data;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory;
use Retaint\Analytics\Model\Types\SubscriberFactory;

class Subscribers {

    public $subscribers = array();
    private $pageNum;
    protected $helper;
    protected $collectionFactory;
    protected $subscriberFactory;

    /**
     * @param \Retaint\Analytics\Helper\Data $helper
     * @param \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory $collectionFactory
     * @param \Retaint\Analytics\Model\Types\SubscriberFactory $subscriberFactory
     */
    public function __construct(
        Data $helper,
        CollectionFactory $collectionFactory,
        SubscriberFactory $subscriberFactory
    ) {
        $this->helper = $helper;
        $this->collectionFactory = $collectionFactory;
        $this->subscriberFactory = $subscriberFactory;
    }

    public function load($pageSize, $pageNum, $sortDir, $filterBy, $id)
    {
        $config = $this->helper->getConfig();
        $this->pageNum = $pageNum;
        if ($id) {
            $subscribers = $this->collectionFactory->create()
                ->addFieldToFilter('main_table.subscriber_id', $id);
        } else {
            $subscribers = $this->collectionFactory->create();
        }
        $subscribers->addFilter('store_id', 'store_id = '.$this->helper->getStore()->getStoreId(), 'string');
        $subscribers->setOrder('subscriber_id', $sortDir);
        $subscribers->setCurPage($pageNum);
        $subscribers->setPageSize($pageSize);

        if ($subscribers->getLastPageNumber() < $pageNum) {
            return $this;
        }

        foreach ($subscribers as $subscriber) {
            $model = $this->subscriberFactory->create();
            $retaintSubscriber = $model->parse($subscriber);
            if ($retaintSubscriber) {
                $this->subscribers[] = $retaintSubscriber;
            }
        }

        return $this->subscribers;
    }
}
