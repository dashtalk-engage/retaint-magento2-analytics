<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */
namespace Retaint\Analytics\Model\Types;
class Product {
    protected $helper;
    protected $productFactory;
    protected $configurableFactory;
    /**
     * @param \Retaint\Analytics\Helper\Data $helper
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\ConfigurableProduct\Model\Product\Type\ConfigurableFactory $configurableFactory
     */
    public function __construct(
        \Retaint\Analytics\Helper\Data $helper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\ConfigurableProduct\Model\Product\Type\ConfigurableFactory $configurableFactory
    ) {
        $this->helper = $helper;
        $this->productFactory = $productFactory;
        $this->configurableFactory = $configurableFactory;
    }
    public function parse($productId, $productAttributes) {
        $product = $this->productFactory->create()->load($productId);
        $parentProductImage = false;
        $this->product_id = $productId;
        $this->entity_id = $product->getData('entity_id');
        $this->entity_type_id = $product->getData('entity_type_id');
        $this->attribute_set_id = $product->getData('attribute_set_id');
        $this->type_id = $product->getData('type_id');
        $this->category_ids = $product->getCategoryIds();
        $parentProductIds = $this->configurableFactory->create()
            ->getParentIdsByChild($productId);
        if($parentProductIds) {
            $this->parent_product_id = $parentProductIds[0];
            $parentProduct = $this->productFactory->create()->load($parentProductIds[0]);
            $parentProductImage = $parentProduct->getImage();
        }
        foreach ($productAttributes as $field => $usesSource) {
            try {
                $value = $product->getData($field);
                if (is_array($value) || is_object($value)) {
                    continue;
                }
                if ($field == 'image') {
                    if((!$value || $value == 'no_selection') && $parentProductImage) {
                        $value = $parentProductImage;
                    }
                    if(!is_array($value)) {
                        $imageUrl = $this->helper->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $value;
                    } else {
                        $imageUrl = null;
                    }
                    $this->$field = $imageUrl;
                    continue;
                }
                if ($usesSource) {
                    $option = $product->getAttributeText($field);
                    if ($value && empty($option) && $option != '0') {
                        continue;
                    }
                    if (is_array($option)) {
                        $value = implode(',', $option);
                    } else {
                        $value = $option;
                    }
                }
                if ($field == 'category_ids') {
                    continue;
                }
                $this->$field = $value;
            } catch (\Exception $e) {
                continue;
            }
        }
        return $this;
    }
}
