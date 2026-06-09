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
use Magento\Framework\App\Response\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Retaint\Analytics\Helper\Data;
use Retaint\Analytics\Model\Types\OrdersFactory;

class Orders extends \Retaint\Analytics\Controller\Module
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var OrdersFactory
     */
    protected $ordersFactory;

    /**
     * @param Context     $context
     * @param JsonFactory $resultJsonFactory
     * @param OrdersFactory $ordersFactory
     * @param Data        $helper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        OrdersFactory $ordersFactory,
        Data $helper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->ordersFactory = $ordersFactory;
        $this->helper = $helper;
        parent::__construct($context);
        parent::initParams();
    }

    /**
     * @return Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $orders = $this->ordersFactory->create();

        if (!$this->isAuthorized() || !$this->isEnabled()) {
            $result->setHttpResponseCode(Http::STATUS_CODE_401);
            $result->setData(['error' => 'Invalid security token or module disabled']);
            return $result;
        }

        $data = $orders->load(
            $this->pageSize,
            $this->pageNum,
            $this->startDate,
            $this->endDate,
            $this->sortDir,
            $this->filterField,
            $this->id
        );

        return $result->setData(['orders' => $data]);
    }
}
