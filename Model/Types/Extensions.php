<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */
namespace Retaint\Analytics\Model\Types;

class Extensions {

    public $extensions = array();

    public function __construct(
        // deprecated this api call
    ) {}

    public function load($pageSize, $pageNum, $sortDir, $filterBy) {
        return $this->extensions;
    }
}
