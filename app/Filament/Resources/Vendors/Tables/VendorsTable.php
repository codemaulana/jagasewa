<?php

namespace App\Filament\Resources\Vendors\Tables;

use App\Models\ManualActivation;
use App\Models\Subscription;
use App\Models\Vendor;
use App\Services\Billing\EntitlementService;
use App\Services\Billing\SubscriptionStateMachine;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VendorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Rental / Toko')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('wa_number')
                    ->label('No. WhatsApp')
                    ->searchable(),
                TextColumn::make('subscription_status')
                    ->label('Paket Langganan')
                    ->badge()
                    ->state(function (Vendor $record) {
                        $entitlement = app(EntitlementService::class);
                        if ($entitlement->isPro($record)) {
                            $end = $record->activeSubscription?->current_period_end;
                            $endStr = $end ? ' (s/d '.$end->format('d/m/y').')' : '';

                            return 'PRO'.$endStr;
                        }
                        if ($entitlement->isInGracePeriod($record)) {
                            return 'GRACE PERIOD';
                        }

                        return 'FREE';
                    })
                    ->color(fn (string $state) => str_starts_with($state, 'PRO') ? 'success' : (str_starts_with($state, 'GRACE') ? 'warning' : 'gray')),
                ToggleColumn::make('is_active')
                    ->label('Status Aktif'),
                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->recordActions([
                Action::make('activatePro')
                    ->label('Aktivasi Pro')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->modalHeading(fn (Vendor $record) => "Aktivasi Paket Pro Manual (Transfer Bank) — {$record->name}")
                    ->modalDescription('Catat bukti transfer dan aktifkan paket Pro untuk vendor ini secara instan.')
                    ->form([
                        Select::make('billing_cycle')
                            ->label('Siklus Langganan')
                            ->options([
                                Subscription::CYCLE_MONTHLY => 'Bulanan (Rp 15.000)',
                                Subscription::CYCLE_QUARTERLY => '3 Bulan (Rp 39.000)',
                                Subscription::CYCLE_ANNUALLY => '1 Tahun (Rp 129.000)',
                            ])
                            ->default(Subscription::CYCLE_MONTHLY)
                            ->required()
                            ->live(),
                        TextInput::make('amount_paid_idr')
                            ->label('Nominal Diterima (Rp)')
                            ->numeric()
                            ->required()
                            ->default(fn ($get) => Subscription::getPlanPrice($get('billing_cycle') ?? Subscription::CYCLE_MONTHLY)),
                        Textarea::make('notes')
                            ->label('Catatan / Nomor Referensi Transfer')
                            ->placeholder('Contoh: Transfer BCA an Budi Utomo ref 109283746')
                            ->required(),
                    ])
                    ->action(function (Vendor $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $stateMachine = app(SubscriptionStateMachine::class);

                            $subscription = Subscription::create([
                                'vendor_id' => $record->id,
                                'plan' => Subscription::PLAN_PRO,
                                'status' => Subscription::STATUS_PENDING_PAYMENT,
                                'billing_cycle' => $data['billing_cycle'],
                                'current_period_start' => now('Asia/Jakarta'),
                            ]);

                            $stateMachine->transition($subscription, Subscription::STATUS_ACTIVE, 'invoice.paid', [
                                'billing_cycle' => $data['billing_cycle'],
                                'gateway_subscription_id' => 'manual_'.Str::random(12),
                            ]);

                            ManualActivation::create([
                                'subscription_id' => $subscription->id,
                                'vendor_id' => $record->id,
                                'activated_by' => auth()->id() ?? 1,
                                'amount_paid_idr' => (int) $data['amount_paid_idr'],
                                'notes' => $data['notes'],
                                'activated_at' => now('Asia/Jakarta'),
                            ]);
                        });

                        Notification::make()
                            ->title('Paket Pro Berhasil Diaktifkan')
                            ->body("Vendor {$record->name} kini aktif berlangganan Pro.")
                            ->success()
                            ->send();
                    }),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
