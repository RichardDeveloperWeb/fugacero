<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboards) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => $this->dashboards->summaryFor($this->user($request)),
        ]);
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }
}
