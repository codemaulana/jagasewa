<?php

namespace App\Filament\Support\Tables;

use App\Helpers\FormatHelper;
use Filament\Tables\Columns\TextColumn;

class MoneyColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->formatStateUsing(fn ($state): string => FormatHelper::rupiah($state))
            ->alignEnd()
            ->extraAttributes(['class' => 'tabular-nums font-medium text-text']);
    }
}
