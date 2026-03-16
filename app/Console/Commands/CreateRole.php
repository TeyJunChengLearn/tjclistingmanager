<?php

namespace App\Console\Commands;

use App\Models\Role;
use Illuminate\Console\Command;

class CreateRole extends Command
{
    protected $signature = 'make:role';
    protected $description = 'Create a new role';

    public function handle(): void
    {
        $name = $this->ask('Role name');

        if (Role::where('name', $name)->exists()) {
            $this->error("Role [{$name}] already exists.");
            return;
        }

        Role::create([
            'name'       => $name,
            'guard_name' => 'web',
        ]);

        $this->info("Role [{$name}] created.");
    }
}
