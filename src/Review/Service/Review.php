<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\GraphQL\Account\Review\Service;

use OxidEsales\GraphQL\Account\Review\Exception\ReviewNotFound;
use OxidEsales\GraphQL\Base\Exception\InvalidLogin;
use OxidEsales\GraphQL\Base\Service\Authentication;
use OxidEsales\GraphQL\Base\Service\Authorization;
use OxidEsales\GraphQL\Catalogue\Review\DataType\Review as ReviewDataType;
use OxidEsales\GraphQL\Catalogue\Review\Service\Review as ReviewService;
use OxidEsales\GraphQL\Catalogue\Shared\Infrastructure\Repository;

final class Review
{
    /** @var Repository */
    private $repository;

    /** @var ReviewService */
    private $reviewService;

    /** @var Authentication */
    private $authenticationService;

    /** @var Authorization */
    private $authorizationService;

    public function __construct(
        Repository $repository,
        ReviewService $reviewService,
        Authentication $authenticationService,
        Authorization $authorizationService
    ) {
        $this->repository            = $repository;
        $this->reviewService         = $reviewService;
        $this->authenticationService = $authenticationService;
        $this->authorizationService  = $authorizationService;
    }

    /**
     * @throws InvalidLogin
     * @throws ReviewNotFound
     *
     * @return true
     */
    public function delete(string $id): bool
    {
        $review = $this->reviewService->review($id);

        //user can delete only its own review, admin can delete any review
        if (
            !$this->authorizationService->isAllowed('DELETE_REVIEW')
            && $this->authenticationService->getUserId() !== $review->getReviewerId()
        ) {
            throw new InvalidLogin('Unauthorized');
        }

        return $this->repository->delete($review->getEshopModel());
    }

    /**
     * @return true
     */
    public function save(ReviewDataType $review): bool
    {
        return $this->repository->saveModel(
            $review->getEshopModel()
        );
    }
}