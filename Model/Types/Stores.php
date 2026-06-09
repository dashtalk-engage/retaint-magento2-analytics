<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */
namespace Retaint\Analytics\Model\Types;

use Retaint\Analytics\Helper\Data;
use Retaint\Analytics\Model\Types\StoreFactory;

class Stores {

    public $stores = array();
    protected $helper;
    protected $storeFactory;
    private $pageNum;

    /**
     * @param \Retaint\Analytics\Helper\Data $helper
     * @param \Retaint\Analytics\Model\Types\StoreFactory $storeFactory
     */
    public function __construct(
        Data $helper,
        StoreFactory $storeFactory
    ) {
        $this->helper = $helper;
        $this->storeFactory = $storeFactory;
    }

    public function load($pageSize, $pageNum)
    {
        $config = $this->helper->getConfig();
        $this->pageNum = $pageNum;

        $stores = $this->helper->getStores();
        $stores = $this->helper->paginate($stores, $pageNum, $pageSize);

        foreach($stores as $store) {
            $retaintStore = $this->storeFactory->create();
            $storeModel = $retaintStore->parse($store);
            if ($storeModel) {
                $this->stores[] = $storeModel;
            }
        }

        return $this->stores;
    }
}
