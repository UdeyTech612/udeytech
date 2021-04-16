<?php
/**
 * Copyright (c) 2021. Udeytech Technologies All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Udeytech\FacebookPixel\Plugin\App\Action;

use Closure;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Udeytech\FacebookPixel\Model\Customer\Context as CustomerSessionContext;

/**
 * Class Context
 * @package Udeytech\FacebookPixel\Plugin\App\Action
 */
class Context
{
    /**
     * @var Session
     */
    public $customerSession;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    public $httpContext;

    /**
     * @param Session $customerSession
     * @param \Magento\Framework\App\Http\Context $httpContext
     */
    public function __construct(
        Session $customerSession,
        \Magento\Framework\App\Http\Context $httpContext
    )
    {
        $this->customerSession = $customerSession;
        $this->httpContext = $httpContext;
    }

    /**
     * @param ActionInterface $subject
     * @param callable $proceed
     * @param RequestInterface $request
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundDispatch(
        ActionInterface $subject,
        Closure $proceed,
        RequestInterface $request
    )
    {
        $customerId = $this->customerSession->getCustomerId();
        if (!$customerId) {
            $customerId = 0;
        }

        $this->httpContext->setValue(
            CustomerSessionContext::CONTEXT_CUSTOMER_ID,
            $customerId,
            false
        );

        return $proceed($request);
    }
}
