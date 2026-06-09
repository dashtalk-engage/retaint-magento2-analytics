<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */
namespace Retaint\Analytics\Model\Types;

use Retaint\Analytics\Helper\Data;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Retaint\Analytics\Model\Types\CategoryFactory;

class Categories {

    public $categories = array();
    protected $helper;
    protected $categoryCollection;
    protected $categoryFactory;
    private $pageNum;

    /**
     * @param \Retaint\Analytics\Helper\Data $helper
     * @param \Magento\Catalog\Model\Resource\Category\CollectionFactory $categoryCollection
     * @param \Retaint\Analytics\Model\Types\CategoryFactory $categoryFactory
     */
    public function __construct(
        Data $helper,
        CollectionFactory $categoryCollection,
        CategoryFactory $categoryFactory
    ) {
        $this->helper = $helper;
        $this->categoryCollection = $categoryCollection;
        $this->categoryFactory = $categoryFactory;
    }

    public function load($pageSize, $pageNum, $startDate = null, $endDate = null, $sortDir, $filterBy, $id)
    {
        $config = $this->helper->getConfig();
        $this->pageNum = $pageNum;

        if ($id) {
            $categories = $this->categoryCollection->create()
                ->addAttributeToFilter('entity_id', $id);
        } elseif ($startDate && $endDate) {
            $from = date('Y-m-d 00:00:00', strtotime($startDate));
            $to = date('Y-m-d 23:59:59', strtotime($endDate));

            $categories = $this->categoryCollection->create()
                ->addAttributeToFilter($filterBy, array('from' => $from, 'to' => $to));
        } else {
            $categories = $this->categoryCollection->create();
        }

        $categories->setOrder('created_at', $sortDir);
        $categories->setCurPage($pageNum);
        $categories->setPageSize($pageSize);

        if ($categories->getLastPageNumber() < $pageNum) {
            return $this;
        }

        
        foreach ($categories as $cat) {
            $category = $this->categoryFactory->create();
            $model = $category->parse($cat);
            if ($model) {
                $this->categories[] = $model;
            }
        }

        return $this->categories;
    }
}
