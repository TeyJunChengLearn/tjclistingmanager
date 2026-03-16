<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    protected $signature = 'make:user';
    protected $description = 'Create a new user with a role';

    public function handle(): void
    {
        $roles = Role::pluck('name')->toArray();

        if (empty($roles)) {
            $this->error('No roles found. Please create roles first at /admin/roles.');
            return;
        }

        $name     = $this->ask('Name');
        $email    = $this->ask('Email address');
        $password = $this->secret('Password');
        $role     = $this->choice('Role', $roles, 0);

        User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
            'role'     => $role,
        ]);

        $this->info("User [{$email}] created with role [{$role}].");
    }
}
