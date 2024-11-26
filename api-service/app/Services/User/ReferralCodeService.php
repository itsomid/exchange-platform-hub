<?php

namespace App\Services\User;

use App\Models\ReferralCode;
use App\Repositories\DTO\ReferralCode\ReferralCodeCreateDTO;
use App\Repositories\Interfaces\ReferralCodeRepositoryInterface;
use App\Services\User\DTO\ReferralCode\ReferralCodeCreateRequestDTO;
use App\Services\User\DTO\ReferralCode\ReferralCodeCreateResponseDTO;
use Illuminate\Database\Eloquent\Collection;

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

    public function lists(int $userId): Collection
    {
        return $this->repository->getByUserId($userId);
    }
}
