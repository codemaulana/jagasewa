<?php

namespace App\Filament\Support\Tables;

use App\Helpers\FormatHelper;
use Filament\Tables\Columns\TextColumn;

class DateColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->formatStateUsing(fn ($state): string => FormatHelper::date($state, 'd M Y'))
            ->extraAttributes(['class' => 'tabular-nums text-text-muted']);
    }
}
