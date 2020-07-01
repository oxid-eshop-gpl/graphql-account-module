<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Account\DataType;

use OxidEsales\Eshop\Application\Model\User as EshopUserModel;
use OxidEsales\GraphQL\Account\WishList\DataType\WishList as WishListDataType;
use OxidEsales\GraphQL\Catalogue\Shared\DataType\DataType;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * @Type()
 */
final class Customer implements DataType
{
    private const WISHLIST_NAME = 'wishlist';

    /** @var EshopUserModel */
    private $customer;

    public function __construct(EshopUserModel $customer)
    {
        $this->customer = $customer;
    }

    public function getEshopModel(): EshopUserModel
    {
        return $this->customer;
    }

    /**
     * @Field()
     */
    public function getId(): ID
    {
        return new ID($this->customer->getId());
    }

    /**
     * @Field()
     */
    public function getFirstName(): string
    {
        return (string) $this->customer->getFieldData('oxfname');
    }

    public function getWishList(): WishListDataType
    {
        return new WishListDataType($this->getEshopModel()->getBasket(self::WISHLIST_NAME));
    }

    public static function getModelClass(): string
    {
        return EshopUserModel::class;
    }
}
