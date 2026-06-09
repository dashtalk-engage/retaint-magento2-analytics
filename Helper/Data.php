<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */
namespace Retaint\Analytics\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Module\ModuleListInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper {

    protected $config;
    protected $store;
    protected $context;
    protected $storeManager;
    protected $scopeConfig;
    protected $logger;
    protected $moduleList;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     */
     public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ModuleListInterface $moduleList
     ) {
         parent::__construct($context);
         $this->context = $context;
         $this->storeManager = $storeManager;
         $this->scopeConfig = $context->getScopeConfig();
         $this->logger = $context->getLogger();
         $this->moduleList = $moduleList;
     }

     public function getBaseDir() {
         return $this->context->getBaseDir().'/app/code/Retaint/';
     }

     public function getConfig() {
         $config = array();
         $config['enabled'] = $this->scopeConfig->getValue('retaint_settings/general/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
         $config['security_token'] = $this->scopeConfig->getValue('retaint_settings/general/security_token', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
         $config['collector_url'] = $this->scopeConfig->getValue('retaint_settings/general/collector_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
         $config['connection_status'] = $this->scopeConfig->getValue('retaint_settings/general/connection_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
         $this->config = $config;
         return $config;
     }

     public function getVersion() {
         return $this->moduleList->getOne('Retaint_Analytics')['setup_version'];
     }

     public function formatDate($str) {
         if($str) {
             if(stripos($str, ' ')) {
                 $str = substr($str, 0, stripos($str, ' '));
             }
         }

         return $str;
     }

     public function toArray($value, $create = false) {
         if($value !== false) {
             return is_array($value) ? $value : array($value);
         } else {
             return $create ? array() : $value;
         }
     }

     public function logException($ex, $msg) {
         $this->logger->debug($ex);
         return false;
     }

     public function log($msg) {
         return $this->logger->info($msg);
     }

     public function getStore() {
         if($this->store == null) {
             $this->store = $this->storeManager->getStore();
         }

         return $this->store;
     }

     public function getStores() {
         return $this->storeManager->getStores();
     }

     public function paginate($array, $pageNumber, $pageSize) {
         $start = $pageNumber * $pageSize;
         return array_slice($array, $start, $pageSize);
     }
 }
