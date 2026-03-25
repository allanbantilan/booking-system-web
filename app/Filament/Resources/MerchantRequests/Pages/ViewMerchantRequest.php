<?php

namespace App\Filament\Resources\MerchantRequests\Pages;

use App\Filament\Resources\MerchantRequests\MerchantRequestResource;
use App\Mail\MerchantCredentialsMail;
use App\Mail\MerchantRequestRejectedMail;
use App\Models\BackendUser;
use App\Models\MerchantRequest;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class ViewMerchantRequest extends ViewRecord
{
    protected static string $resource = MerchantRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn (MerchantRequest $record): bool => $record->status === 'pending')
                ->form([
                    \Filament\Forms\Components\Textarea::make('decision_note')
                        ->label('Decision Note')
                        ->required()
                        ->maxLength(1000),
                ])
                ->action(function (MerchantRequest $record, array $data): void {
                    if (BackendUser::query()->where('email', $record->user->email)->exists()) {
                        $record->update([
                            'status' => 'rejected',
                            'decision_note' => 'Backend account already exists for this email.',
                            'handled_by' => auth('backend')->id(),
                            'handled_at' => now(),
                        ]);
                        Mail::to($record->user->email)->send(
                            new MerchantRequestRejectedMail($record, $record->decision_note)
                        );

                        return;
                    }

                    $plainPassword = Str::password(12, true, false, true, false);
                    $loginUrl = url('/admin');

                    DB::transaction(function () use ($record, $plainPassword, $loginUrl, $data): void {
                        $backendUser = BackendUser::query()->create([
                            'name' => $record->user->name,
                            'email' => $record->user->email,
                            'password' => $plainPassword,
                            'email_verified_at' => now(),
                        ]);

                        $merchantRole = Role::firstOrCreate([
                            'name' => 'merchant',
                            'guard_name' => 'backend',
                        ]);

                        $backendUser->syncRoles([$merchantRole->name]);

                        $record->update([
                            'status' => 'approved',
                            'backend_user_id' => $backendUser->id,
                            'decision_note' => $data['decision_note'] ?? null,
                            'handled_by' => auth('backend')->id(),
                            'handled_at' => now(),
                        ]);

                        DB::afterCommit(function () use ($backendUser, $plainPassword, $loginUrl): void {
                            Mail::to($backendUser->email)->send(
                                new MerchantCredentialsMail($backendUser, $plainPassword, $loginUrl)
                            );
                        });
                    });
                }),
            Action::make('reject')
                ->label('Reject')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (MerchantRequest $record): bool => $record->status === 'pending')
                ->form([
                    \Filament\Forms\Components\Textarea::make('decision_note')
                        ->label('Reason')
                        ->required()
                        ->maxLength(1000),
                ])
                ->action(function (MerchantRequest $record, array $data): void {
                    $record->update([
                        'status' => 'rejected',
                        'decision_note' => $data['decision_note'] ?? null,
                        'handled_by' => auth('backend')->id(),
                        'handled_at' => now(),
                    ]);
                    Mail::to($record->user->email)->send(
                        new MerchantRequestRejectedMail($record, $record->decision_note)
                    );
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Section::make('Request')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'warning',
                            }),
                        TextEntry::make('created_at')
                            ->label('Submitted')
                            ->dateTime('M j, Y g:i A'),
                        TextEntry::make('user.name')
                            ->label('Requester'),
                        TextEntry::make('user.email')
                            ->label('Email'),
                        TextEntry::make('backendUser.email')
                            ->label('Backend Account')
                            ->default('—'),
                        TextEntry::make('handledBy.name')
                            ->label('Handled By')
                            ->default('—'),
                        TextEntry::make('handled_at')
                            ->label('Handled At')
                            ->formatStateUsing(fn ($state) => $state ? $state->format('M j, Y g:i A') : '—'),
                        TextEntry::make('decision_note')
                            ->label('Decision Note')
                            ->default('—')
                            ->columnSpanFull(),
                        TextEntry::make('message')
                            ->label('Request Message')
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'whitespace-pre-line']),
                    ]),
            ]);
    }
}
