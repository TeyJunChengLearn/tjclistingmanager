<?php

namespace App\Livewire;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', \App\Models\User::count()),
            Stat::make('Total Roles', \App\Models\Role::count()),
        ];
    }
}
