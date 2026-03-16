<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form as SchemaForm;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ChangePassword extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';
    protected static ?string $title = 'Change Password';
    protected static ?int $navigationSort = 99;

    public function getView(): string
    {
        return 'filament.pages.change-password';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('currentPassword')
                ->label('Current Password')
                ->password()
                ->revealable()
                ->required()
                ->currentPassword()
                ->autocomplete('current-password')
                ->dehydrated(false),
            TextInput::make('password')
                ->label('New Password')
                ->password()
                ->revealable()
                ->required()
                ->rule(Password::default())
                ->autocomplete('new-password')
                ->live(debounce: 500)
                ->same('passwordConfirmation'),
            TextInput::make('passwordConfirmation')
                ->label('Confirm New Password')
                ->password()
                ->revealable()
                ->required()
                ->autocomplete('new-password')
                ->dehydrated(false),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            SchemaForm::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    SchemaActions::make([
                        Action::make('save')
                            ->label('Update Password')
                            ->submit('save'),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        auth()->user()->update([
            'password' => Hash::make($data['password']),
        ]);

        $this->form->fill();

        Notification::make()
            ->title('Password updated successfully.')
            ->success()
            ->send();
    }
}
