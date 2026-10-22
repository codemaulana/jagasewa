<?php

namespace App\Filament\Support\Components;

use Filament\Forms\Components\TextInput;

class RupiahInput extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prefix('Rp')
            ->numeric()
            ->minValue(0)
            ->extraInputAttributes([
                'class' => 'tabular-nums',
            ]);
    }
}
