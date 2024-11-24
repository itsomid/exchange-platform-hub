<?php

namespace App\Functions;

use Morilog\Jalali\Jalalian;

trait Jalali
{
    public function created_at()
    {
        return Jalalian::forge($this->created_at)->format('%A, %d %B %Y');
    }

    public function updated_at()
    {
        return Jalalian::forge($this->created_at)->format('%A, %d %B %Y');
    }
}
