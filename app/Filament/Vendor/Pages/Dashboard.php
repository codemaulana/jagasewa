<?php

namespace App\Filament\Vendor\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use UnitEnum;

class Dashboard extends BaseDashboard
{
    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Dashboard Operasional';
}
