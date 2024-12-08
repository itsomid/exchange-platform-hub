<?php

namespace App\Services\User;

use App\Exceptions\NotFoundException;
use App\Exceptions\User\ReferralCodeDoesNotBelongsToUser;
use App\Models\ReferralCode;
use App\Models\ReferralCodeUsage;
use App\Repositories\DTO\ReferralCode\ReferralCodeCreateDTO;
use App\Repositories\Interfaces\ReferralCodeRepositoryInterface;
use App\Repositories\Interfaces\ReferralCodeUsageRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\User\DTO\ReferralCode\ReferralCodeCreateRequestDTO;
use App\Services\User\DTO\ReferralCode\ReferralCodeCreateResponseDTO;
use App\Services\User\DTO\ReferralCode\ReferralCodeGetListsResponseDTO;
use App\Services\User\DTO\ReferralCode\ReferralCodeGetOwnerProfitsRequestDTO;
use App\Services\User\DTO\ReferralCode\ReferralCodeGetOwnerProfitsResponseDTO;
use App\Services\User\DTO\ReferralCode\ReferralCodeGetRegisteredUsersRequestDTO;
use App\Services\User\DTO\ReferralCode\ReferralCodeRegisteredUsersResponseDTO;

readonly class ReferralCodeService
{
    public function __construct(
        private ReferralCodeRepositoryInterface $repository,
        private UserRepositoryInterface $userRepository,
        private ReferralCodeUsageRepositoryInterface $referralCodeUsageRepository
    ) {}

    public function create(ReferralCodeCreateRequestDTO $createRequestDTO): ReferralCodeCreateResponseDTO
    {
        $introducerFee = $createRequestDTO->getMaxFee() - $createRequestDTO->getFriendFee();
        $createdModel = $this->repository->create(
            resolve(ReferralCodeCreateDTO::class)
                ->setUserId($createRequestDTO->getUserId())
                ->setFriendFee($createRequestDTO->getFriendFee())
                ->setIntroducerFee($introducerFee)
                ->setUsageLimit($createRequestDTO->getUsageLimit())
                ->setCode($code = ReferralCode::generateReferralCode())
        );

        return resolve(ReferralCodeCreateResponseDTO::class)
            ->setReferralModel($createdModel);
    }

    public function lists(int $userId): array
    {
        $codes = $this->repository->getByUserId($userId);

        return $codes->map(fn (ReferralCode $code) => resolve(ReferralCodeGetListsResponseDTO::class)
            ->setId($code->id)
            ->setCreatedAt($code->created_at)
            ->setIntroducerFee($code->introducer_fee)
            ->setFriendFee($code->friend_fee)
            ->setTotalAmountReceived((int) $code->transactions_sum_amount)
            ->setTotalCountTransaction($code->referral_code_usage_count)
            ->setTotalFriendUsage($code->registered_users_count)
            ->setCode($code->code)
        )->toArray();
    }

    /**
     * @throws ReferralCodeDoesNotBelongsToUser
     * @throws NotFoundException
     */
    public function getRegisteredUsers(ReferralCodeGetRegisteredUsersRequestDTO $requestDTO): array
    {
        $referralModel = $this->repository->getReferralCodeByCode($requestDTO->getReferralCode());

        if (is_null($referralModel)) {
            throw new NotFoundException;
        }
        if ($referralModel->user_id !== $requestDTO->getUserId()) {
            throw new ReferralCodeDoesNotBelongsToUser;
        }

        $users = $this->userRepository->getReferredUsers($referralModel->id);

        return $users->map(fn ($user) => resolve(ReferralCodeRegisteredUsersResponseDTO::class)
            ->setUserId($user->id)
            ->setUserEmail($user->email)
            ->setUserName($user->name)
            ->setTotalOrders($user->referred_transactions_count)
            ->setTotalProfit((string) $user->referred_transactions_sum_amount)
        )->toArray();
    }

    public function showTransactionsForReferredUser() {}

    public function getOwnerProfits(ReferralCodeGetOwnerProfitsRequestDTO $requestDTO): array
    {
        $usagesCollection = $this->referralCodeUsageRepository->getReceivedProfits($requestDTO->getReferredUserId());

        return $usagesCollection->map(fn (ReferralCodeUsage $usage) => resolve(ReferralCodeGetOwnerProfitsResponseDTO::class)
            ->setAmount((string) $usage->transaction->amount)
            ->setReceivedDate($usage->transaction->created_at)
            ->setTransactionDescription($usage->transaction->description)
        )->toArray();
    }
}
