<?php
/**
 * Retaint Analytics for Magento 2.
 *
 * @copyright Copyright (c) 2026 Retaint (https://retaint.com)
 * @license   https://opensource.org/licenses/OSL-3.0  Open Software License 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Retaint\Analytics\Controller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * Base controller for all Retaint feed endpoints.
 *
 * Parses the common pagination/date/sort request parameters and enforces
 * token authentication for every concrete endpoint that extends it.
 */
abstract class Module extends Action implements HttpGetActionInterface
{
    /**
     * Request header the collector must send to authenticate.
     */
    public const AUTH_HEADER = 'X-Retaint-Token';

    protected $pageSize = null;
    protected $pageNum = 0;
    protected $startDate = null;
    protected $endDate = null;
    protected $sortDir = 'asc';
    protected $filterField = 'created_at';
    protected $id = null;
    protected $ignoreCost = null;
    protected $customAttr = null;

    /**
     * @var \Retaint\Analytics\Helper\Data
     */
    protected $helper;

    /**
     * Read the supported feed parameters off the request.
     *
     * @return void
     */
    protected function initParams()
    {
        if ((bool) $pageSize = $this->getRequest()->getParam('page_size')) {
            $this->pageSize = $pageSize;
        }
        if ((bool) $pageNum = $this->getRequest()->getParam('page_num')) {
            $this->pageNum = $pageNum;
        }
        if ((bool) $startDate = $this->getRequest()->getParam('start_date')) {
            $this->startDate = $startDate;
            if ((bool) $endDate = $this->getRequest()->getParam('end_date')) {
                $this->endDate = $endDate;
            } else {
                $this->endDate = date('Y-m-d');
            }
        } elseif ((bool) $updatedStartDate = $this->getRequest()->getParam('updated_start_date')) {
            $this->filterField = 'updated_at';
            $this->startDate = $updatedStartDate;
            if ((bool) $updatedEndDate = $this->getRequest()->getParam('updated_end_date')) {
                $this->endDate = $updatedEndDate;
            } else {
                $this->endDate = date('Y-m-d');
            }
        }
        if ((bool) $sortDir = $this->getRequest()->getParam('sort_dir')) {
            $this->sortDir = $sortDir;
        }
        if ((bool) $id = $this->getRequest()->getParam('id')) {
            $this->id = $id;
        }
        if ((bool) $customAttr = $this->getRequest()->getParam('customAttr')) {
            $this->customAttr = $customAttr;
        }
        if ((bool) $ignoreCost = $this->getRequest()->getParam('ignore_cost')) {
            $this->ignoreCost = $ignoreCost;
        }
    }

    /**
     * Whether the module is enabled in configuration.
     *
     * @return bool
     */
    protected function isEnabled()
    {
        return (bool) $this->helper->getConfig()['enabled'];
    }

    /**
     * Authenticate the caller against this store's secret token.
     *
     * The collector must send the store's secret key in the X-Retaint-Token
     * header. The comparison is constant-time to avoid timing attacks, and the
     * raw token is never logged.
     *
     * @return bool
     */
    protected function isAuthorized()
    {
        $token = trim((string) $this->helper->getConfig()['security_token']);
        $authToken = trim((string) $this->getRequest()->getHeader(self::AUTH_HEADER));

        if ($token === '' || $authToken === '') {
            return false;
        }

        if (!hash_equals($token, $authToken)) {
            $this->helper->log('Retaint feed request with invalid security token.');
            return false;
        }

        return true;
    }
}
