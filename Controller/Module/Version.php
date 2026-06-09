<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Retaint\Analytics\Controller\Module;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ProductMetadataInterfaceFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Retaint\Analytics\Helper\Data;

/**
 * Lightweight handshake endpoint returning module/platform versions.
 *
 * Intentionally unauthenticated so the collector can discover the plugin
 * version before exchanging the secret token. Returns no store data.
 */
class Version extends \Retaint\Analytics\Controller\Module
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var ProductMetadataInterfaceFactory
     */
    protected $productMetadataInterfaceFactory;

    /**
     * @param Context                         $context
     * @param JsonFactory                     $resultJsonFactory
     * @param ProductMetadataInterfaceFactory $productMetadataInterfaceFactory
     * @param Data                            $helper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ProductMetadataInterfaceFactory $productMetadataInterfaceFactory,
        Data $helper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->productMetadataInterfaceFactory = $productMetadataInterfaceFactory;
        $this->helper = $helper;
        parent::__construct($context);
        parent::initParams();
    }

    /**
     * @return Json
     */
    public function execute()
    {
        $productMetadata = $this->productMetadataInterfaceFactory->create();

        $result = $this->resultJsonFactory->create();
        $data = new \stdClass();
        $data->retaintPluginVersion = (string) $this->helper->getVersion();
        $data->magentoVersion = (string) $productMetadata->getVersion();
        $data->magentoEdition = (string) $productMetadata->getEdition();
        $data->phpVersion = (string) phpversion();
        $data->moduleEnabled = $this->helper->getConfig()['enabled'];
        $data->apiVersion = '3.0';
        $data->memoryLimit = @ini_get('memory_limit');
        $data->maxExecutionTime = @ini_get('max_execution_time');

        return $result->setData($data);
    }
}
