<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Compliance\LaunchGate\LaunchGate;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only view of the real-money launch gate. There is intentionally no
 * update action, for any role.
 */
class LaunchControlController extends Controller
{
    public function __invoke(LaunchGate $gate): Response
    {
        return Inertia::render('admin/LaunchControl', [
            'gate' => $gate->status()->toArray(),
        ]);
    }
}
