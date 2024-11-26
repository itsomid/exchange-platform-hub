<?php

namespace App\Services\User;

use App\Models\ReferralCode;
use App\Repositories\DTO\ReferralCode\ReferralCodeCreateDTO;
use App\Repositories\Interfaces\ReferralCodeRepositoryInterface;
use App\Services\User\DTO\ReferralCode\ReferralCodeCreateRequestDTO;
use App\Services\User\DTO\ReferralCode\ReferralCodeCreateResponseDTO;
use App\Services\User\DTO\ReferralCode\ReferralCodeGetListsResponseDTO;

readonly class ReferralCodeService
{
    public function __construct(private ReferralCodeRepositoryInterface $repository) {}

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
}
