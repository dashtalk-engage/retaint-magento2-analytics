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
use Magento\Backend\Block\Widget\Button;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Retaint\Analytics\Helper\Data;

/**
 * Renders the one-click "Connect to Retaint" button plus the current
 * connection status, and wires the button to the admin onboarding controller.
 */
class Connect extends \Magento\Config\Block\System\Config\Form\Field
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
     * Remove the scope label/column for a cleaner full-width row.
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $connected = (string) $this->helper->getConfig()['connection_status'] === 'connected';
        $registerUrl = $this->getUrl('retaint/connect/register');

        $button = $this->getLayout()->createBlock(Button::class)
            ->setData([
                'id' => 'retaint_connect_button',
                'label' => __('Connect to Retaint'),
                'class' => 'primary',
            ]);

        $status = $connected
            ? '<span id="retaint_connect_status" style="color:#2f8a3b;font-weight:bold;">'
                . __('Connected') . '</span>'
            : '<span id="retaint_connect_status" style="color:#9a9a9a;">'
                . __('Not connected') . '</span>';

        $script = "<script>require(['jquery'], function ($) {"
            . "$('#retaint_connect_button').on('click', function (e) {"
            . "e.preventDefault();"
            . "var \$b = $(this); \$b.prop('disabled', true);"
            . "$('#retaint_connect_status').text('" . __('Connecting…') . "');"
            . "$.ajax({ url: '" . $registerUrl . "', type: 'POST', dataType: 'json',"
            . " data: { form_key: window.FORM_KEY }, showLoader: true })"
            . ".done(function (r) {"
            . "if (r && r.success) {"
            . "$('#retaint_connect_status').css({color:'#2f8a3b','font-weight':'bold'}).text('" . __('Connected') . "');"
            . "} else {"
            . "$('#retaint_connect_status').css({color:'#e02b27'}).text(r && r.message ? r.message : '" . __('Connection failed') . "');"
            . "}"
            . "}).fail(function () {"
            . "$('#retaint_connect_status').css({color:'#e02b27'}).text('" . __('Connection failed') . "');"
            . "}).always(function () { \$b.prop('disabled', false); });"
            . "});"
            . "});</script>";

        return '<div style="display:flex;align-items:center;gap:12px;">'
            . $button->toHtml()
            . $status
            . '</div>'
            . '<p class="note"><span>'
            . __('One click sends this store&rsquo;s URL and secret key to Retaint so polling can begin automatically.')
            . '</span></p>'
            . $script;
    }
}
