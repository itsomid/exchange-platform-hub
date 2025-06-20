<?php

namespace App\Enums;

enum StockStatusEnum: string
{
   case ACTIVE = 'active';
   case INACTIVE = 'inactive';

   const array TYPE_LABEL = [
    self::ACTIVE->value => 'فعال',
    self::INACTIVE->value => 'غیر فعال',
   ];

   const array TYPE_COLOR = [
    self::ACTIVE->value => 'success',
    self::INACTIVE->value => 'danger',
   ];

   public function label() : string
   {
    return self::TYPE_LABEL[$this->value] ?? '';
   }

   public function color() : string
   {
    return self::TYPE_COLOR[$this->value] ?? '';
   }
} 