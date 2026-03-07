<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    /**
     * Optional page title (e.g. "Log in"). Shown as "TaskBook - {title}".
     */
    public function __construct(
        public ?string $title = null,
    ) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}
