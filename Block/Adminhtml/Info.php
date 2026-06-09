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
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\Registry;

/**
 * Branded information panel shown at the top of the Retaint config section.
 */
class Info extends \Magento\Backend\Block\Widget implements RendererInterface
{
    private const LOGO_URL = 'https://retaint.com/wp-content/uploads/2026/05/cropped-retaint-logo-4444.png';
    private const SUPPORT_EMAIL = 'support@retaint.com';
    private const WEBSITE_URL = 'https://retaint.com';

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Context  $context
     * @param Registry $registry
     * @param array    $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $this->setElement($element);

        $logo = $this->escapeUrl(self::LOGO_URL);
        $email = $this->escapeHtml(self::SUPPORT_EMAIL);
        $website = $this->escapeUrl(self::WEBSITE_URL);

        return '<div style="background:#FFFFFF;margin-bottom:10px;padding:15px;border:1px solid #e3e3e3;border-radius:4px;">'
            . '<img src="' . $logo . '" alt="Retaint" style="max-width:180px;margin-bottom:10px;" />'
            . '<h1 style="margin:5px 0;">The Best Platform For Growing Your Online Store</h1>'
            . '<p>Retaint helps you generate more revenue, cultivate loyal customers and optimize product strategy.</p>'
            . '<table width="500px" border="0" style="margin-top:10px;">'
            . '<tr><td>Questions, comments, need help? Send us an email.</td>'
            . '<td><a href="mailto:' . $email . '">' . $email . '</a></td></tr>'
            . '<tr><td height="30">Visit our website:</td>'
            . '<td><a href="' . $website . '" target="_blank">retaint.com</a></td></tr>'
            . '</table>'
            . '</div>';
    }
}
