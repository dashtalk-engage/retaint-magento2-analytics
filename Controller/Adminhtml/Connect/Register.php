<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Retaint\Analytics\Controller\Adminhtml\Connect;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Retaint\Analytics\Helper\Data;
use Retaint\Analytics\Setup\Patch\Data\InitializeSettings;

/**
 * One-click onboarding: registers this store with the Retaint collector by
 * POSTing the store URL + secret key, then records the connection status.
 *
 * If the collector is unreachable the merchant can still connect manually by
 * copying the Store URL and Secret Key shown on the config page.
 */
class Register extends Action implements HttpPostActionInterface
{
    /**
     * ACL resource required to run this action.
     */
    public const ADMIN_RESOURCE = 'Retaint_Analytics::config';

    private const PATH_STATUS = 'retaint_settings/general/connection_status';
    private const PATH_CONNECTED_AT = 'retaint_settings/general/connected_at';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var DateTime
     */
    private $date;

    /**
     * @param Context               $context
     * @param JsonFactory           $resultJsonFactory
     * @param Curl                  $curl
     * @param Data                  $helper
     * @param StoreManagerInterface $storeManager
     * @param WriterInterface       $configWriter
     * @param DateTime              $date
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Curl $curl,
        Data $helper,
        StoreManagerInterface $storeManager,
        WriterInterface $configWriter,
        DateTime $date
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->curl = $curl;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->configWriter = $configWriter;
        $this->date = $date;
        parent::__construct($context);
    }

    /**
     * @return Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $config = $this->helper->getConfig();

        $token = trim((string) ($config['security_token'] ?? ''));
        if ($token === '') {
            return $result->setData([
                'success' => false,
                'message' => (string) __('No secret key is configured yet. Save the configuration and try again.'),
            ]);
        }

        $collectorUrl = trim((string) ($config['collector_url'] ?? ''));
        if ($collectorUrl === '') {
            $collectorUrl = InitializeSettings::DEFAULT_COLLECTOR_URL;
        }

        $store = $this->storeManager->getStore();
        $payload = [
            'store_url' => $store->getBaseUrl(UrlInterface::URL_TYPE_WEB, true),
            'security_token' => $token,
            'platform' => 'magento2',
            'module_version' => (string) $this->helper->getVersion(),
            'store_name' => (string) $store->getName(),
        ];

        try {
            $endpoint = rtrim($collectorUrl, '/') . '/connect';
            $this->curl->setHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]);
            $this->curl->setOption(CURLOPT_TIMEOUT, 15);
            $this->curl->post($endpoint, json_encode($payload));
            $status = (int) $this->curl->getStatus();

            if ($status >= 200 && $status < 300) {
                $this->configWriter->save(self::PATH_STATUS, 'connected');
                $this->configWriter->save(self::PATH_CONNECTED_AT, $this->date->gmtDate());

                return $result->setData([
                    'success' => true,
                    'message' => (string) __('Connected to Retaint.'),
                ]);
            }

            $this->configWriter->save(self::PATH_STATUS, 'failed');

            return $result->setData([
                'success' => false,
                'message' => (string) __('Retaint returned an unexpected response (HTTP %1).', $status),
            ]);
        } catch (\Throwable $e) {
            $this->helper->log('Retaint connect handshake failed: ' . $e->getMessage());
            $this->configWriter->save(self::PATH_STATUS, 'failed');

            return $result->setData([
                'success' => false,
                'message' => (string) __('Could not reach Retaint. You can connect manually using the Store URL and Secret Key below.'),
            ]);
        }
    }
}
