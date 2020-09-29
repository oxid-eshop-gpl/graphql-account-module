<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Tests\Codeception\Acceptance\Basket;

use Codeception\Util\HttpCode;
use OxidEsales\GraphQL\Account\Tests\Codeception\Acceptance\MultishopBaseCest;
use OxidEsales\GraphQL\Account\Tests\Codeception\AcceptanceTester;

final class BasketMultishopCest extends MultishopBaseCest
{
    private const EXISTING_USERNAME = 'existinguser@oxid-esales.com';

    private const USERNAME = 'user@oxid-esales.com';

    private const PASSWORD = 'useruser';

    private const PUBLIC_BASKET = '_test_basket_public'; //owned by shop1 user

    private const PRIVATE_BASKET = '_test_basket_private_ex'; //owned by existinguser

    private const BASKET_NOTICE_LIST = 'noticelist';

    public function testGetNotOwnedBasketFromDifferentShop(AcceptanceTester $I): void
    {
        $I->login(self::USERNAME, self::PASSWORD, 2);

        $this->queryBasket($I, self::PRIVATE_BASKET, 2);

        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    public function testGetPublicBasketFromDifferentShopNoToken(AcceptanceTester $I): void
    {
        $this->queryBasket($I, self::PUBLIC_BASKET, 2);

        $I->seeResponseCodeIs(HttpCode::OK);
    }

    public function testGetPrivateBasketFromDifferentShopWithTokenForMallUser(AcceptanceTester $I): void
    {
        $I->updateConfigInDatabase('blMallUsers', true, 'bool');

        $I->login(self::EXISTING_USERNAME, self::PASSWORD, 2);

        $this->queryBasket($I, self::PRIVATE_BASKET, 2);

        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
    }

    public function testGetPrivateBasketFromSubShopWithToken(AcceptanceTester $I): void
    {
        $I->login(self::EXISTING_USERNAME, self::PASSWORD, 2);

        $this->queryBasket($I, self::PRIVATE_BASKET, 2);

        $I->seeResponseCodeIs(HttpCode::OK);
    }

    public function testCreatePrivateBasketFromDifferentShop(AcceptanceTester $I): void
    {
        $I->login(self::USERNAME, self::PASSWORD);

        $result = $this->createBasket($I, self::BASKET_NOTICE_LIST, 'false');
        $I->seeResponseCodeIs(HttpCode::OK);
        $basketId = $result['data']['basketCreate']['id'];

        $I->logout();
        $I->login(self::USERNAME, self::PASSWORD, 2);

        $this->queryBasket($I, $basketId, 2);
        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);

        $I->logout();
        $I->login(self::USERNAME, self::PASSWORD);

        $this->removeBasket($I, $basketId, 1);
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    public function testCreatePrivateBasketFromDifferentShopForMallUser(AcceptanceTester $I): void
    {
        $I->updateConfigInDatabase('blMallUsers', true, 'bool');

        $I->login(self::USERNAME, self::PASSWORD);

        $result = $this->createBasket($I, self::BASKET_NOTICE_LIST, 'false');
        $I->seeResponseCodeIs(HttpCode::OK);
        $basketId = $result['data']['basketCreate']['id'];

        $I->logout();
        $I->login(self::USERNAME, self::PASSWORD, 2);

        $this->createBasket($I, self::BASKET_NOTICE_LIST, 'false', 2);
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);

        $this->queryBasket($I, $basketId, 2);
        $I->seeResponseCodeIs(HttpCode::OK);

        $this->removeBasket($I, $basketId, 2);
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    private function queryBasket(AcceptanceTester $I, string $id, int $shopId): array
    {
        $I->sendGQLQuery(
            'query {
                basket(id: "' . $id . '") {
                    id
                    public
                }
            }',
            null,
            0,
            $shopId
        );

        $I->seeResponseIsJson();

        return $I->grabJsonResponseAsArray();
    }

    private function createBasket(AcceptanceTester $I, string $title, string $public = 'true', int $shopId = 1): array
    {
        $I->sendGQLQuery(
            'mutation {
                basketCreate(basket: {title: "' . $title . '", public: ' . $public . '}) {
                    id
                }
            }',
            null,
            0,
            $shopId
        );

        $I->seeResponseIsJson();

        return $I->grabJsonResponseAsArray();
    }

    private function removeBasket(AcceptanceTester $I, string $id, int $shopId = 1): array
    {
        $I->sendGQLQuery(
            'mutation {
                basketRemove(id: "' . $id . '")
            }',
            null,
            0,
            $shopId
        );

        $I->seeResponseIsJson();

        return $I->grabJsonResponseAsArray();
    }
}
