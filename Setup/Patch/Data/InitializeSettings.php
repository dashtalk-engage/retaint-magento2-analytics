<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Retaint\Analytics\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Math\Random;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Seeds default configuration on install:
 *  - generates a cryptographically strong per-store secret token,
 *  - enables the module,
 *  - sets the default Retaint collector URL.
 *
 * Replaces the legacy Setup/InstallData.php, which used the deprecated
 * InstallDataInterface and wrote directly to core_config_data / url_rewrite.
 */
class InitializeSettings implements DataPatchInterface
{
    /**
     * Default collector endpoint the one-click "Connect" handshake posts to.
     */
    public const DEFAULT_COLLECTOR_URL = 'https://datapulse.retaint.com';

    private const PATH_ENABLED = 'retaint_settings/general/enabled';
    private const PATH_TOKEN = 'retaint_settings/general/security_token';
    private const PATH_COLLECTOR_URL = 'retaint_settings/general/collector_url';

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Random
     */
    private $random;

    /**
     * @param WriterInterface      $configWriter
     * @param ScopeConfigInterface $scopeConfig
     * @param Random               $random
     */
    public function __construct(
        WriterInterface $configWriter,
        ScopeConfigInterface $scopeConfig,
        Random $random
    ) {
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->random = $random;
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        // Only generate a token if one does not already exist (idempotent).
        if (!$this->scopeConfig->getValue(self::PATH_TOKEN)) {
            $token = $this->random->getRandomString(40);
            $this->configWriter->save(self::PATH_TOKEN, $token);
        }

        if ($this->scopeConfig->getValue(self::PATH_ENABLED) === null) {
            $this->configWriter->save(self::PATH_ENABLED, '1');
        }

        if (!$this->scopeConfig->getValue(self::PATH_COLLECTOR_URL)) {
            $this->configWriter->save(self::PATH_COLLECTOR_URL, self::DEFAULT_COLLECTOR_URL);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }
}
