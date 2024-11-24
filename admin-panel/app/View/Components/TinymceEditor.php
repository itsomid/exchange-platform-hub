<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TinymceEditor extends Component
{
    /**
     * Create a new component instance.
     */
    public $selector;

    public $value;

    public function __construct($selector, $value = '')
    {
        $this->selector = $selector;
        $this->value = $value;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.tinymce-editor');
    }
}
