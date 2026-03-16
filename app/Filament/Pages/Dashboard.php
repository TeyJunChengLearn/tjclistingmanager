<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AdminOverviewWidget;
use App\Filament\Widgets\EditorOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return match (auth()->user()->role) {
            'admin'  => [AdminOverviewWidget::class],
            'editor' => [EditorOverviewWidget::class],
            default  => [],
        };
    }
}
