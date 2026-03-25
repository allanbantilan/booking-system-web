<?php

namespace App\Filament\Resources\MerchantRequests\Tables;

use App\Mail\MerchantCredentialsMail;
use App\Mail\MerchantRequestRejectedMail;
use App\Models\BackendUser;
use App\Models\MerchantRequest;
use Filament\Forms\Components\Textarea;
use App\Filament\Resources\MerchantRequests\MerchantRequestResource;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class MerchantRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Requester')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Requested')
                    ->since()
                    ->sortable(),
                TextColumn::make('handledBy.name')
                    ->label('Handled By')
                    ->toggleable(),
                TextColumn::make('handled_at')
                    ->label('Handled At')
                    ->dateTime('M j, Y g:i A')
                    ->toggleable(),
                TextColumn::make('message')
                    ->label('Message')
                    ->limit(60)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('decision_note')
                    ->label('Decision Note')
                    ->limit(60)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->recordUrl(fn (MerchantRequest $record) => MerchantRequestResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make()
                    ->label('View')
                    ->color('primary'),
                Action::make('approve')
                    ->label('Approve')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (MerchantRequest $record): bool => $record->status === 'pending')
                    ->form([
                        Textarea::make('decision_note')
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
                    ->requiresConfirmation()
                    ->visible(fn (MerchantRequest $record): bool => $record->status === 'pending')
                    ->form([
                        Textarea::make('decision_note')
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
            ])
            ->toolbarActions([])
            ->defaultSort('created_at', 'desc');
    }
}
