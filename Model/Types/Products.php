<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */
namespace Retaint\Analytics\Model\Types;
class Products {
    public $products = array();
    protected $helper;
    protected $productFactory;
    protected $productParserFactory;
    protected $attributeCollectionFactory;
    private $pageNum;
    protected $resource;
    private $productAttributes = array();
    /**
     * @param \Retaint\Analytics\Helper\Data $helper
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productFactory
     * @param \Retaint\Analytics\Model\Types\ProductFactory $productParserFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory $attributeCollectionFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \Retaint\Analytics\Helper\Data $helper,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productFactory,
        \Retaint\Analytics\Model\Types\ProductFactory $productParserFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory $attributeCollectionFactory,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->helper = $helper;
        $this->productFactory = $productFactory;
        $this->productParserFactory = $productParserFactory;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->resource = $resource;
    }
    public function load($pageSize, $pageNum, $startDate = null, $endDate = null, $sortDir, $filterBy, $id)
    {
        $config = $this->helper->getConfig();
        $this->pageNum = $pageNum;
        $this->_getProductAttribtues();
        if( $id ) {
            $collection = $this->productFactory->create()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', $id)
                ->setFlag('has_stock_status_filter', false);
        } elseif ($startDate && $endDate) {
            $from = date('Y-m-d 00:00:00', strtotime($startDate));
            $to = date('Y-m-d 23:59:59', strtotime($endDate));
            $collection = $this->productFactory->create()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter($filterBy, array('from' => $from, 'to' => $to));
        } else {
            $collection = $this->productFactory->create()
                ->addAttributeToSelect('*')
                ->setFlag('has_stock_status_filter', false);
        }

        $collection->setStore($this->helper->getStore());
        $collection->setOrder('entity_id', 'asc');
        $collection->setCurPage($pageNum);
        $collection->setPageSize($pageSize);
        if ($collection->getLastPageNumber() < $pageNum) {
            return $this;
        }
        foreach ($collection as $product) {
            $productId = $product->getId();
            $model = $this->productParserFactory->create()->parse($productId, $this->productAttributes);
            if ($model) {
                $model->cross_sell_products = $this->_getCrossSellProducts($product);
                $model->up_sell_products = $this->_getUpSellProducts($product);
                $model->related_products = $this->_getRelatedProducts($product);
                $this->products[] = $model;
            }
        }
        return $this->products;
    }
    protected function _getProductAttribtues()
    {
        if (!$this->productAttributes) {
            $attributes = $this->attributeCollectionFactory->create()
                ->addFieldToFilter(\Magento\Eav\Model\Entity\Attribute\Set::KEY_ENTITY_TYPE_ID, 4)
                ->load()
                ->getItems();
            foreach ($attributes as $attribute) {
                if (!$attribute) {
                    continue;
                }
                $this->productAttributes[$attribute->getData('attribute_code')] = $attribute->usesSource();
            }
        }
    }
    protected function _getCrossSellProducts($product)
    {
        $productArray = array();
        $collection = $product->getCrossSellProductCollection();
        if ($collection) {
            foreach ($collection as $item) {
                $productArray[] = $item->getId();
            }
        }
        return $productArray;
    }
    protected function _getUpSellProducts($product)
    {
        $productArray = array();
        $collection = $product->getUpSellProductCollection();
        if ($collection) {
            foreach ($collection as $item) {
                $productArray[] = $item->getId();
            }
        }
        return $productArray;
    }
    protected function _getRelatedProducts($product)
    {
        $productArray = array();
        $collection = $product->getRelatedProductCollection();
        if ($collection) {
            foreach ($collection as $item) {
                $productArray[] = $item->getId();
            }
        }
        return $productArray;
    }
}
