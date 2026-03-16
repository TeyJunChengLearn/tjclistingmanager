<?php

namespace App\Filament\Resources\MyListing\RelationManagers;

use App\Models\Activity;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Activity Log';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('activity_type')
                ->label('Activity Type')
                ->options([
                    'call'      => 'Call',
                    'whatsapp'  => 'WhatsApp',
                    'visit'     => 'Visit',
                    'email'     => 'Email',
                    'note'      => 'Note',
                ])
                ->required(),
            Select::make('outcome_code')
                ->label('Outcome')
                ->options([
                    'connected'        => 'Connected',
                    'no_answer'        => 'No Answer',
                    'busy'             => 'Busy',
                    'voicemail'        => 'Left Voicemail',
                    'interested'       => 'Interested',
                    'not_interested'   => 'Not Interested',
                    'price_too_high'   => 'Price Too High',
                    'negotiating'      => 'Negotiating',
                    'agreed'           => 'Agreed',
                ])
                ->nullable(),
            Textarea::make('outcome_note')
                ->label('Note')
                ->rows(3)
                ->columnSpanFull()
                ->nullable(),
            DateTimePicker::make('followed_up_at')
                ->label('Followed Up At')
                ->default(now())
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('followed_up_at')
                    ->label('Date')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('activity_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'call'     => 'info',
                        'whatsapp' => 'success',
                        'visit'    => 'warning',
                        'email'    => 'gray',
                        'note'     => 'gray',
                        default    => 'gray',
                    }),
                TextColumn::make('outcome_code')
                    ->label('Outcome')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'connected', 'interested', 'agreed' => 'success',
                        'no_answer', 'busy', 'voicemail'    => 'warning',
                        'not_interested', 'price_too_high'  => 'danger',
                        default                             => 'gray',
                    })
                    ->placeholder('—'),
                TextColumn::make('outcome_note')
                    ->label('Note')
                    ->limit(60)
                    ->placeholder('—'),
                TextColumn::make('user.name')
                    ->label('By')
                    ->placeholder('—'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        return $data;
                    }),
            ]);
    }
}
