<?php

namespace App\Http\Controllers;

use App\Services\AdminDashboardService;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __construct(private readonly AdminDashboardService $dashboards) {}

    public function index(): Response
    {
        return Inertia::render('admin/Dashboard', [
            'stats' => $this->dashboards->summary(),
            'charts' => [
                'monthly_spending' => $this->dashboards->monthlySpendingFor(),
            ],
        ]);
    }
}
