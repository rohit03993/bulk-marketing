<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * Optional page title (e.g. "Dashboard"). Shown as "TaskBook - {title}".
     */
    public function __construct(
        public ?string $title = null,
    ) {
        if ($this->title === null && request()->route()) {
            $this->title = \Illuminate\Support\Str::title(str_replace('.', ' ', request()->route()->getName()));
        }
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}
