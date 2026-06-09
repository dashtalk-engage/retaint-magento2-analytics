<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */
namespace Retaint\Analytics\Model\Types;

class Category {

    protected $category;

    /**
     * \Magento\Catalog\Model\Category $category
     */
    public function __construct(
        \Magento\Catalog\Model\Category $category
    ) {
        $this->category = $category;
    }

    public function parse($cat) {

        $category = $this->category->load($cat->getId());

        $this->category_id = $category->getId();
        $this->entity_id = $category->getData('entity_id');
        $this->entity_type_id = $category->getData('entity_type_id');
        $this->attribute_set_id = $category->getData('attribute_set_id');
        $this->parent_id = $category->getData('parent_id');
        $this->created_at = $category->getData('created_at');
        $this->updated_at = $category->getData('updated_at');
        $this->path = $category->getData('path');
        $this->position = $category->getData('position');
        $this->level = $category->getData('level');
        $this->children_count = $category->getData('children_count');
        $this->available_sort_by = $category->getData('available_sort_by');
        $this->include_in_menu = $category->getData('include_in_menu');
        $this->name = $category->getData('name');
        $this->url_key = $category->getData('url_key');

        return $this;
    }
}
