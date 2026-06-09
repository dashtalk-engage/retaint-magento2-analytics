<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Retaint\Analytics\Test\Integration\Controller;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Verifies token authentication on the feed endpoints.
 *
 * Run inside a Magento integration test environment:
 *   vendor/bin/phpunit -c dev/tests/integration/phpunit.xml.dist \
 *     app/code/Retaint/Analytics/Test/Integration
 */
class AuthTest extends AbstractController
{
    private const TOKEN = 'integration-test-secret-token';

    /**
     * @magentoConfigFixture default_store retaint_settings/general/enabled 1
     * @magentoConfigFixture default_store retaint_settings/general/security_token integration-test-secret-token
     */
    public function testRejectsRequestWithoutToken(): void
    {
        $this->dispatch('retaint/module/orders');
        $this->assertSame(401, $this->getResponse()->getHttpResponseCode());
        $this->assertStringContainsString('Invalid security token', $this->getResponse()->getBody());
    }

    /**
     * @magentoConfigFixture default_store retaint_settings/general/enabled 1
     * @magentoConfigFixture default_store retaint_settings/general/security_token integration-test-secret-token
     */
    public function testRejectsRequestWithWrongToken(): void
    {
        $this->getRequest()->setHeader('X-Retaint-Token', 'wrong-token');
        $this->dispatch('retaint/module/orders');
        $this->assertSame(401, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * @magentoConfigFixture default_store retaint_settings/general/enabled 1
     * @magentoConfigFixture default_store retaint_settings/general/security_token integration-test-secret-token
     */
    public function testAcceptsRequestWithValidToken(): void
    {
        $this->getRequest()->setHeader('X-Retaint-Token', self::TOKEN);
        $this->dispatch('retaint/module/orders');
        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
        $this->assertStringContainsString('orders', $this->getResponse()->getBody());
    }

    /**
     * @magentoConfigFixture default_store retaint_settings/general/enabled 0
     * @magentoConfigFixture default_store retaint_settings/general/security_token integration-test-secret-token
     */
    public function testRejectsWhenModuleDisabled(): void
    {
        $this->getRequest()->setHeader('X-Retaint-Token', self::TOKEN);
        $this->dispatch('retaint/module/orders');
        $this->assertSame(401, $this->getResponse()->getHttpResponseCode());
    }
}
