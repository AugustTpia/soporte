<?php

namespace App\Assistant\Presentation\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Asistente')]
class AssistantPage extends Component
{
    public function render(): View
    {
        return view('assistant.livewire.assistant-page');
    }
}
