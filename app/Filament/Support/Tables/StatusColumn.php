<?php

namespace App\Filament\Support\Tables;

use Filament\Tables\Columns\TextColumn;

class StatusColumn extends TextColumn
{
    protected ?string $enumClass = null;

    public function forEnum(string $enumClass): static
    {
        $this->enumClass = $enumClass;

        return $this
            ->badge()
            ->formatStateUsing(fn ($state) => $this->resolveEnum($state)?->getLabel() ?? $state)
            ->color(fn ($state) => $this->resolveEnum($state)?->getColor() ?? 'gray')
            ->icon(fn ($state) => $this->resolveEnum($state)?->getIcon());
    }

    public function enumClass(string $enumClass): static
    {
        return $this->forEnum($enumClass);
    }

    protected function resolveEnum($state)
    {
        if (! $this->enumClass || ! class_exists($this->enumClass)) {
            return null;
        }

        if ($state instanceof $this->enumClass) {
            return $state;
        }

        return $this->enumClass::tryFrom((string) $state);
    }
}
