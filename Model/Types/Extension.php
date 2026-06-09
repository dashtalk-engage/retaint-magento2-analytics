<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */
namespace Retaint\Analytics\Model\Types;

class Extension {

    public function __construct() {
        // nothing to inject
    }

    public function parse($extension, $attr)
    {
        $this->name = $extension;
        $this->active = (string) $attr->active;
        $this->version = (string) $attr->version;
        return $this;
    }
}
