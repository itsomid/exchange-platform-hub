<?php

namespace App\Services;

use App\Models\Account;
use App\Repositories\ProductAccessRepository;

class UserAccountService
{
    /**
     * Get the balance of a user account by user ID.
     */
    public function getBalance(int $userId): int
    {
        return Account::getUserBalance($userId);
    }

    /**
     * Get the IDs of products purchased by the user.
     */
    public function getPurchasedProductIds(int $userId, bool $excludeFree = false): array
    {
        $productAccesses = resolve(ProductAccessRepository::class)
            ->getProductPurchasedId($userId, $excludeFree);

        return $productAccesses->map(fn ($access) => $access->product_id)->toArray();
    }

    /**
     * Get the IDs of child products that the user has access to.
     */
    public function getProductChildrenIdsAccess(int $userId): array
    {
        $productService = resolve(ProductService::class);

        return $productService->getProductTreeLeaves(
            $this->getPurchasedProductIds($userId)
        );
    }

    /**
     * Check if the user has access to a specific product.
     */
    public function hasAccessToProduct(int $userId, int $productId): bool
    {
        return in_array($productId, $this->getProductChildrenIdsAccess($userId));
    }
}
