<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\NewsletterStatus\Infrastructure;

use OxidEsales\Eshop\Application\Model\NewsSubscribed as EshopNewsletterSubscriptionStatusModel;
use OxidEsales\Eshop\Application\Model\User as EshopUserModel;
use OxidEsales\GraphQL\Account\Account\DataType\Customer as CustomerDataType;
use OxidEsales\GraphQL\Account\Account\Exception\CustomerNotFound;
use OxidEsales\GraphQL\Account\Account\Infrastructure\Repository as CustomerRepository;
use OxidEsales\GraphQL\Account\NewsletterStatus\DataType\NewsletterStatus as NewsletterStatusType;
use OxidEsales\GraphQL\Account\NewsletterStatus\DataType\NewsletterStatusSubscribe as NewsletterStatusSubscribeType;
use OxidEsales\GraphQL\Account\NewsletterStatus\DataType\NewsletterStatusUnsubscribe as NewsletterStatusUnsubscribeType;
use OxidEsales\GraphQL\Account\NewsletterStatus\DataType\Subscriber as SubscriberDataType;
use OxidEsales\GraphQL\Account\NewsletterStatus\Exception\NewsletterStatusNotFound;
use OxidEsales\GraphQL\Base\Service\Legacy as LegacyService;

final class Repository
{
    /** @var CustomerRepository */
    private $customerRepository;

    /** @var LegacyService */
    private $legacyService;

    public function __construct(
        CustomerRepository $customerRepository,
        LegacyService $legacyService
    ) {
        $this->customerRepository = $customerRepository;
        $this->legacyService      = $legacyService;
    }

    /**
     * @throws NewsletterStatusNotFound
     */
    public function getByUserId(
        string $userId
    ): NewsletterStatusType {
        /** @var EshopNewsletterSubscriptionStatusModel */
        $model = oxNew(NewsletterStatusType::getModelClass());

        if (!$model->loadFromUserId($userId)) {
            throw NewsletterStatusNotFound::byUserId($userId);
        }

        return new NewsletterStatusType($model);
    }

    public function getByEmail(string $email): NewsletterStatusType
    {
        return new NewsletterStatusType($this->getEhopModelByEmail($email));
    }

    public function getUnsubscribeByEmail(string $email): NewsletterStatusUnsubscribeType
    {
        return new NewsletterStatusUnsubscribeType($this->getEhopModelByEmail($email));
    }

    public function optIn(SubscriberDataType $subscriber, NewsletterStatusType $newsletterStatus): bool
    {
        /** @var EshopNewsletterSubscriptionStatusModel $newsletterStatusModel */
        $newsletterStatusModel = $newsletterStatus->getEshopModel();
        $newsletterStatusModel->setOptInStatus(1);

        return $newsletterStatusModel->updateSubscription($subscriber->getEshopModel());
    }

    public function unsubscribe(SubscriberDataType $subscriber): bool
    {
        return $this->setNewsSubscription($subscriber, false);
    }

    public function subscribe(
        SubscriberDataType $subscriber,
        bool $forceOptin
    ): NewsletterStatusType {
        if ($forceOptin) {
            $this->unsubscribe($subscriber);
        }
        $this->setNewsSubscription($subscriber, true);

        return $this->getByEmail($subscriber->getUserName());
    }

    /**
     * @throws CustomerNotFound
     */
    public function createNewsletterUser(NewsletterStatusSubscribeType $input): CustomerDataType
    {
        /** @var EshopUserModel $user */
        $user = oxNew(EshopUserModel::class);

        $user->assign(
            [
                'oxactive'   => 1,
                'oxrights'   => 'user',
                'oxsal'      => $input->salutation(),
                'oxfname'    => $input->firstName(),
                'oxlname'    => $input->lastName(),
                'oxusername' => $input->email(),
            ]
        );

        return $this->customerRepository->createUser($user);
    }

    /**
     * @throws NewsletterStatusNotFound
     */
    private function getEhopModelByEmail(string $email): EshopNewsletterSubscriptionStatusModel
    {
        /** @var EshopNewsletterSubscriptionStatusModel $newsletterStatusModel */
        $newsletterStatusModel = oxNew(NewsletterStatusType::getModelClass());

        if (!$newsletterStatusModel->loadFromEmail($email)) {
            throw NewsletterStatusNotFound::byEmail($email);
        }

        return $newsletterStatusModel;
    }

    private function setNewsSubscription(SubscriberDataType $subscriber, bool $flag): bool
    {
        $sendOptinMail = $this->legacyService->getConfigParam('blOrderOptInEmail');

        return $subscriber->getEshopModel()->setNewsSubscription($flag, $sendOptinMail);
    }
}
