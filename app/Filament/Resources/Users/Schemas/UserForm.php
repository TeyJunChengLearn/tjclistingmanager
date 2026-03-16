<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Role;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->live(),
                TextInput::make('password_confirmation')
                    ->password()
                    ->label('Confirm Password')
                    ->required(fn (string $operation) => $operation === 'create')
                    ->same('password')
                    ->dehydrated(false),
                Select::make('role')
                    ->options(fn () => Role::pluck('name', 'name')->toArray())
                    ->required(),
            ]);
    }
}
