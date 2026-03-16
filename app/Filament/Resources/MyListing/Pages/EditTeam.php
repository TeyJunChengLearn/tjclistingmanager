<?php

namespace App\Filament\Resources\MyListing\Pages;

use App\Filament\Resources\MyListing\TeamResource;
use App\Mail\TeamInvitationMail;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Mail;

class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    protected function isOwner(): bool
    {
        return $this->record->user_id === auth()->id();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->disabled(fn (): bool => ! $this->isOwner()),
        ]);
    }

    protected function getFormActions(): array
    {
        if (! $this->isOwner()) {
            return [];
        }

        return parent::getFormActions();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addMember')
                ->label('Add Member')
                ->icon('heroicon-o-user-plus')
                ->visible(fn (): bool => $this->isOwner())
                ->form([
                    TextInput::make('email')
                        ->label('Email Address')
                        ->email()
                        ->required(),
                    Select::make('role')
                        ->label('Role')
                        ->options([
                            'member'  => 'Member',
                            'manager' => 'Manager',
                        ])
                        ->default('member')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $team = $this->record;
                    $email = $data['email'];
                    $role = $data['role'];

                    $user = User::where('email', $email)->first();

                    if ($user) {
                        $alreadyMember = $team->users()->where('users.id', $user->id)->exists();

                        if ($alreadyMember) {
                            Notification::make()
                                ->title('Already a member')
                                ->body("{$email} is already in this team.")
                                ->warning()
                                ->send();
                            return;
                        }

                        $team->users()->attach($user->id, ['role' => $role]);

                        Notification::make()
                            ->title('Member added')
                            ->body("{$user->name} has been added to the team.")
                            ->success()
                            ->send();
                    } else {
                        Mail::to($email)->send(
                            new TeamInvitationMail(
                                team: $team,
                                inviterName: auth()->user()->name,
                                inviteeEmail: $email,
                            )
                        );

                        Notification::make()
                            ->title('Invitation sent')
                            ->body("No account found for {$email}. An invitation email has been sent.")
                            ->info()
                            ->send();
                    }
                }),
            DeleteAction::make()
                ->visible(fn (): bool => $this->isOwner()),
        ];
    }
}
