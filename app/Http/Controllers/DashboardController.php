<?php

namespace App\Http\Controllers;

use App\Infrastructure\Remote\RemoteDataClient;
use App\Infrastructure\Remote\RemoteRbacClient;
use Illuminate\View\View;

final class DashboardController
{
    public function __construct(
        private readonly RemoteDataClient $data,
        private readonly RemoteRbacClient $rbac,
    ) {
    }

    public function __invoke(): View
    {
        $dash = $this->data->dashboard();
        $perms = $this->rbac->myPermissions();

        return view('dashboard', [
            'dash' => $dash,
            'perms' => $perms,
        ]);
    }
}
