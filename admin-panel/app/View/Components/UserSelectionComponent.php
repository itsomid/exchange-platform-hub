<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class UserSelectionComponent extends Component
{
    public string $inputName;

    public bool $multiple = false;

    public string|int $selected;

    public ?string $selectedLabel = null;
    public bool $disabled = false;
    public bool $required = false;

    /**
     * Create a new component instance.
     */
    public function __construct(string $inputName, bool $multiple, string|int $selected, ?string $selectedLabel = null,bool $disabled=false,bool $required=false)
    {
        $this->inputName = $inputName;
        $this->multiple = $multiple;
        $this->selected = $selected;
        $this->selectedLabel = $selectedLabel;
        $this->disabled = $disabled;
        $this->required = $required;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.user-selection-component');
    }
}
