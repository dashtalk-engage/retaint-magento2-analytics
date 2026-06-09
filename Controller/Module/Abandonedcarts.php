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
use Retaint\Analytics\Model\Types\AbandonedCartsFactory;

class Abandonedcarts extends \Retaint\Analytics\Controller\Module
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var AbandonedCartsFactory
     */
    protected $abandonedCartsFactory;

    /**
     * @param Context     $context
     * @param JsonFactory $resultJsonFactory
     * @param AbandonedCartsFactory $abandonedCartsFactory
     * @param Data        $helper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        AbandonedCartsFactory $abandonedCartsFactory,
        Data $helper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->abandonedCartsFactory = $abandonedCartsFactory;
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
        $abandonedCarts = $this->abandonedCartsFactory->create();

        if (!$this->isAuthorized() || !$this->isEnabled()) {
            $result->setHttpResponseCode(Http::STATUS_CODE_401);
            $result->setData(['error' => 'Invalid security token or module disabled']);
            return $result;
        }

        $data = $abandonedCarts->load(
            $this->pageSize,
            $this->pageNum,
            $this->startDate,
            $this->endDate,
            $this->sortDir,
            $this->filterField,
            $this->id
        );

        return $result->setData(['carts' => $data]);
    }
}
