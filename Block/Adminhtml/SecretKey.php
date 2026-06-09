<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Retaint\Analytics\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Retaint\Analytics\Helper\Data;

/**
 * Renders the store's secret key (the token the collector must send).
 */
class SecretKey extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param Context $context
     * @param Data    $helper
     * @param array   $data
     */
    public function __construct(
        Context $context,
        Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $token = trim((string) $this->helper->getConfig()['security_token']);

        return '<code>' . $this->escapeHtml($token) . '</code>';
    }
}
