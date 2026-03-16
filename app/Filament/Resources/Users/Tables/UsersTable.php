<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('role')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActionsColumnLabel(new HtmlString(Blade::render('<x-heroicon-o-cog-6-tooth style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-right:4px" /> Actions')))
            ->recordActions([
                Action::make('changeRole')
                    ->label('Change Role')
                    ->icon('heroicon-o-shield-check')
                    ->visible(fn () => Auth::user()->role === 'admin')
                    ->fillForm(fn ($record) => ['role' => $record->role])
                    ->schema([
                        Select::make('role')
                            ->options(fn () => \App\Models\Role::pluck('name', 'name')->toArray())
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update(['role' => $data['role']]);
                    })
                    ->successNotificationTitle('Role updated'),
                EditAction::make(),
            ])
            ->checkIfRecordIsSelectableUsing(fn ($record) => $record->id !== Auth::id())
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->role === 'admin'),
                ]),
            ]);
    }
}
