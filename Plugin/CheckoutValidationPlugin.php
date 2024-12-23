<?php
/**
 * Copyright © 2024 LucasZit. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @author Lucas Pereira
 */

declare(strict_types=1);

namespace LucasZit\LockCheckout\Plugin;

use Exception;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use LucasZit\LockCheckout\Model\AdminConfiguration;
use Magento\Framework\Message\ManagerInterface;

/**
 * Class CheckoutValidationPlugin to lock customer with lock checkout flag
 */
class CheckoutValidationPlugin
{
    /**
     * CheckoutValidationPlugin Constructor
     *
     * @param CustomerSession $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param AdminConfiguration $adminConfiguration
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        private readonly CustomerSession $customerSession,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly AdminConfiguration $adminConfiguration,
        private readonly ManagerInterface $messageManager,
    ) {}

    /**
     * Before dispatch method to validate lock_checkout attribute.
     *
     * @param Action $subject
     * @return null
     */
    public function beforeDispatch(Action $subject): null
    {
        if (!$this->adminConfiguration->getModuleEnable() || !$this->customerSession->isLoggedIn()) {
            return null;
        }

        try {
            $customerId = (int)$this->customerSession->getCustomerId();

            if ($this->isCustomerLocked($customerId)) {

                if (!$this->adminConfiguration->getRedirectOnLockCheckout()) {
                    $this->messageManager->addErrorMessage(
                        __($this->adminConfiguration->getMessage())
                    );
                    $this->redirectTo($subject, '/');
                }

                $this->redirectTo($subject, '/lock-checkout-message');
            }

        } catch (Exception $e) {
            $this->redirectTo($subject, '/');
        }

        return null;
    }

    /**
     * Get Customer custom attribute Lock Checkout value.
     *
     * @param int $customerId
     * @return bool
     * @throws NoSuchEntityException|LocalizedException
     */
    private function isCustomerLocked(int $customerId): bool
    {
        return (bool) $this->customerRepository
            ->getById($customerId)
            ->getCustomAttribute('lock_checkout')
            ->getValue();
    }

    /**
     * Redirect to a given URL.
     *
     * @param Action $subject
     * @param string $url
     * @return void
     */
    private function redirectTo(Action $subject, string $url): void
    {
        $subject->getResponse()
            ->setRedirect($url)
            ->sendResponse();
        exit;
    }

}

