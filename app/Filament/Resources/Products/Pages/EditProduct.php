<?php

namespace App\Filament\Resources\Products\Pages;

use Filament\Actions\Action;
use App\Models\ProductModeration;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Services\ProductModerationService;
use App\Filament\Resources\Products\ProductResource;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // USER: Ajukan Aktivasi
            Action::make('requestReactivation')
                ->label('Ajukan Aktif Kembali')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(
                    fn() =>
                    ! auth()->user()->is_admin
                        && ! $this->record->is_active
                )
                ->disabled(fn() => (bool) $this->record->pendingReactivationRequest)
                ->schema([
                    TextInput::make('reason')
                        ->label('Alasan Pengajuan')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (array $data, ProductModerationService $service) {
                    try {
                        $service->requestReactivation(
                            $this->record,
                            auth()->user(),
                            $data['reason']
                        );

                        Notification::make()
                            ->title('Pengajuan aktivasi ulang dikirim')
                            ->success()
                            ->send();
                    } catch (\DomainException $e) {

                        Notification::make()
                            ->title($e->getMessage())
                            ->warning()
                            ->send();
                    }

                    $this->refreshFormData(['latestReactivationRequest']);
                }),

            // ADMIN: Approve
            Action::make('approveReactivation')
                ->label('Setujui Aktivasi')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(
                    fn() =>
                    auth()->user()->is_admin
                        && (bool) $this->record->pendingReactivationRequest
                )
                ->action(function (ProductModerationService $service) {

                    try {
                        $service->approveReactivation(
                            $this->record,
                            auth()->user()
                        );

                        Notification::make()
                            ->title('Produk diaktifkan kembali')
                            ->success()
                            ->send();
                    } catch (\DomainException $e) {

                        Notification::make()
                            ->title($e->getMessage())
                            ->warning()
                            ->send();
                    }

                    $this->refreshFormData(['is_active']);
                }),

            // ADMIN: Reject
            Action::make('rejectReactivation')
                ->label('Tolak Aktivasi')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(
                    fn() =>
                    auth()->user()->is_admin
                        && (bool) $this->record->pendingReactivationRequest
                )
                ->schema([
                    TextInput::make('reason')
                        ->label('Alasan Penolakan')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (array $data, ProductModerationService $service) {

                    try {
                        $service->rejectReactivation(
                            $this->record,
                            auth()->user(),
                            $data['reason']
                        );

                        Notification::make()
                            ->title('Pengajuan aktivasi ditolak')
                            ->success()
                            ->send();
                    } catch (\DomainException $e) {

                        Notification::make()
                            ->title($e->getMessage())
                            ->warning()
                            ->send();
                    }

                    $this->refreshFormData(['latestReactivationRequest']);
                }),

            DeleteAction::make(),
        ];
    }
}
