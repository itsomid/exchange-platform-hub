<?php

namespace App\View\Components;

use App\Models\Admin;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AdminSelectionComponent extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(public string $inputName, public string $role, public string $placeholderName, public ?int $defaultValue = null)
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $admins = Admin::query()->role($this->role)->select('id', 'first_name', 'last_name')->get();

        return view('components.admin-selection-component', compact('admins'));
    }
}
