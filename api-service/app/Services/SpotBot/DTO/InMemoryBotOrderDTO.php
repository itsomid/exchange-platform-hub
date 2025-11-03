<?php

namespace App\Services\SpotBot\DTO;

use App\Enums\SpotOrderSideEnum;
use App\Enums\SpotOrderTypeEnum;
use App\Enums\SpotOrderStatusEnum;

class InMemoryBotOrderDTO
{
    public string $id;
    public int $user_id;
    public int $market_id;
    public string $quantity;
    public string $filled_quantity;
    public string $price;
    public SpotOrderSideEnum $side;
    public SpotOrderTypeEnum $type;
    public SpotOrderStatusEnum $status;
    public int $created_at;
    public int $updated_at;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->user_id = $data['user_id'];
        $this->market_id = $data['market_id'];
        $this->quantity = $data['quantity'];
        $this->filled_quantity = $data['filled_quantity'] ?? '0';
        $this->price = $data['price'];
        $this->side = is_string($data['side']) 
            ? SpotOrderSideEnum::from($data['side']) 
            : $data['side'];
        $this->type = is_string($data['type']) 
            ? SpotOrderTypeEnum::from($data['type']) 
            : $data['type'];
        $this->status = is_string($data['status']) 
            ? SpotOrderStatusEnum::from($data['status']) 
            : $data['status'];
        $this->created_at = $data['created_at'] ?? time();
        $this->updated_at = $data['updated_at'] ?? time();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'market_id' => $this->market_id,
            'quantity' => $this->quantity,
            'filled_quantity' => $this->filled_quantity,
            'price' => $this->price,
            'side' => $this->side->value,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}

