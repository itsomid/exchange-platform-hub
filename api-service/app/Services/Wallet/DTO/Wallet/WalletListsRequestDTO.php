<?php

namespace App\Services\Wallet\DTO\Wallet;

class WalletListsRequestDTO
{
    private int $userId;
    private int $page = 1;
    private int $perPage = 20;
    private bool $hideZeroBalance = false;

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setPage(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPerPage(int $perPage): self
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function setHideZeroBalance(bool $hideZeroBalance): self
    {
        $this->hideZeroBalance = $hideZeroBalance;

        return $this;
    }

    public function getHideZeroBalance(): bool
    {
        return $this->hideZeroBalance;
    }
}
