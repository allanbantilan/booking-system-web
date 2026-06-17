<?php

namespace App\Filament\Resources\CancellationRequests\Tables;

use App\Models\Booking;
use App\Models\ReservationCancellationRequest;
use App\Services\Reservations\ReservationCancellationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class CancellationRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('requested_at', 'desc')
            ->columns([
                TextColumn::make('booking.title')
                    ->label('Booking')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reservation.status')
                    ->label('Reservation')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? $state->value : $state)
                    ->sortable(),
                TextColumn::make('booking_start')
                    ->label('Booking Start')
                    ->state(fn (ReservationCancellationRequest $record) => self::resolveStartDate($record))
                    ->dateTime('M j, Y'),
                TextColumn::make('status')
                    ->label('Request Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ReservationCancellationRequest::STATUS_APPROVED => 'success',
                        ReservationCancellationRequest::STATUS_REJECTED => 'danger',
                        ReservationCancellationRequest::STATUS_EXPIRED => 'gray',
                        default => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('requested_at')
                    ->label('Requested')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
                TextColumn::make('reservation.total_price')
                    ->label('Total')
                    ->money('PHP')
                    ->sortable(),
                TextColumn::make('reservation.payment.status')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ?: 'n/a')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'succeeded',
                        'danger' => 'failed',
                    ]),
                TextColumn::make('reservation.payment.receipt.receipt_number')
                    ->label('Receipt')
                    ->formatStateUsing(fn ($state) => $state ?: 'n/a')
                    ->toggleable(),
                TextColumn::make('refund_status')
                    ->label('Refund')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ReservationCancellationRequest::REFUND_PENDING => 'warning',
                        ReservationCancellationRequest::REFUND_PROCESSED => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Request Status')
                    ->options([
                        ReservationCancellationRequest::STATUS_REQUESTED => 'Requested',
                        ReservationCancellationRequest::STATUS_APPROVED => 'Approved',
                        ReservationCancellationRequest::STATUS_REJECTED => 'Rejected',
                        ReservationCancellationRequest::STATUS_EXPIRED => 'Expired',
                    ]),
                SelectFilter::make('refund_status')
                    ->label('Refund Status')
                    ->options([
                        ReservationCancellationRequest::REFUND_NOT_REQUIRED => 'Not Required',
                        ReservationCancellationRequest::REFUND_PENDING => 'Pending',
                        ReservationCancellationRequest::REFUND_PROCESSED => 'Processed',
                    ]),
                SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'succeeded' => 'Succeeded',
                        'failed' => 'Failed',
                    ])
                    ->query(function ($query, array $data) {
                        if (!($data['value'] ?? null)) {
                            return;
                        }

                        $query->whereHas('reservation.payment', function ($paymentQuery) use ($data): void {
                            $paymentQuery->where('status', $data['value']);
                        });
                    }),
                SelectFilter::make('booking')
                    ->relationship('booking', 'title')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->color('primary')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->visible(fn (ReservationCancellationRequest $record): bool => $record->status === ReservationCancellationRequest::STATUS_REQUESTED)
                    ->action(function (ReservationCancellationRequest $record): void {
                        self::runReview(fn (ReservationCancellationService $service, $merchant) => $service->approve($record, $merchant), 'Cancellation request approved.');
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->visible(fn (ReservationCancellationRequest $record): bool => $record->status === ReservationCancellationRequest::STATUS_REQUESTED)
                    ->form([
                        Textarea::make('merchant_note')
                            ->label('Reason')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (ReservationCancellationRequest $record, array $data): void {
                        self::runReview(fn (ReservationCancellationService $service, $merchant) => $service->reject($record, $merchant, $data['merchant_note']), 'Cancellation request rejected.');
                    }),
                Action::make('markRefundProcessed')
                    ->label('Mark refunded')
                    ->color('success')
                    ->icon('heroicon-o-banknotes')
                    ->requiresConfirmation()
                    ->modalHeading('Mark refund as processed?')
                    ->modalDescription('Use this after the refund has been completed outside this system.')
                    ->visible(fn (ReservationCancellationRequest $record): bool =>
                        $record->status === ReservationCancellationRequest::STATUS_APPROVED
                        && $record->refund_status === ReservationCancellationRequest::REFUND_PENDING
                    )
                    ->action(function (ReservationCancellationRequest $record): void {
                        self::runReview(fn (ReservationCancellationService $service, $merchant) => $service->markRefundProcessed($record, $merchant), 'Refund marked as processed.');
                    }),
                Action::make('refundViaPayMaya')
                    ->label('Refund via Maya')
                    ->color('primary')
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->requiresConfirmation()
                    ->modalHeading('Refund via Maya?')
                    ->modalDescription('This calls the Maya refund API. The local refund status updates only after Maya accepts it.')
                    ->visible(fn (ReservationCancellationRequest $record): bool =>
                        $record->status === ReservationCancellationRequest::STATUS_APPROVED
                        && $record->refund_status === ReservationCancellationRequest::REFUND_PENDING
                    )
                    ->action(function (ReservationCancellationRequest $record): void {
                        self::runReview(fn (ReservationCancellationService $service, $merchant) => $service->refundViaPayMaya($record, $merchant), 'Refund sent to Maya.');
                    }),
            ])
            ->toolbarActions([]);
    }

    private static function runReview(callable $callback, string $success): void
    {
        $merchant = auth('backend')->user();

        try {
            $callback(app(ReservationCancellationService::class), $merchant);
        } catch (ValidationException $exception) {
            Notification::make()
                ->title($exception->validator->errors()->first() ?: 'Unable to review request.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()->title($success)->success()->send();
    }

    private static function resolveStartDate(ReservationCancellationRequest $record): ?\Illuminate\Support\Carbon
    {
        $booking = $record->booking;

        if (in_array($booking?->booking_type, [Booking::TYPE_ACCOMMODATION, Booking::TYPE_RENTAL], true)) {
            return $record->reservation?->check_in_date;
        }

        return $booking?->event_date;
    }
}
