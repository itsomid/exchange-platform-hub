<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Morilog\Jalali\Jalalian;

class JalaliConvertor extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $date,
        public string $format = '%A, %d %B %Y',
    )
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $jalaliDate = $this->date ? Jalalian::forge($this->date)->format($this->format) : '-';
        return view('components.jalali-convertor', ['jalaliDate' => $jalaliDate]);
    }
}
