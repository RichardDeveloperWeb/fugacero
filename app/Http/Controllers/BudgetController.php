<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetRequest;
use App\Http\Requests\UpdateBudgetRequest;
use App\Models\Budget;
use App\Models\User;
use App\Services\BudgetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function __construct(private readonly BudgetService $budgets) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Budget::class);

        return Inertia::render('budgets/Index', $this->budgets->summaryFor(
            $this->user($request),
            $this->month($request),
        ));
    }

    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        $budget = $this->budgets->create($this->user($request), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Presupuesto creado.']);

        return to_route('budgets.index', ['month' => $this->monthFor($budget)]);
    }

    public function update(UpdateBudgetRequest $request, Budget $budget): RedirectResponse
    {
        $this->budgets->update($budget, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Presupuesto actualizado.']);

        return to_route('budgets.index', ['month' => $this->monthFor($budget)]);
    }

    public function destroy(Request $request, Budget $budget): RedirectResponse
    {
        Gate::authorize('delete', $budget);

        $this->budgets->delete($budget);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Presupuesto eliminado.']);

        return to_route('budgets.index', ['month' => $this->monthFor($budget)]);
    }

    private function month(Request $request): string
    {
        $validated = $request->validate([
            'month' => ['sometimes', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $month = $validated['month'] ?? null;

        return is_string($month) ? $month : now()->format('Y-m');
    }

    private function monthFor(Budget $budget): string
    {
        return sprintf('%04d-%02d', $budget->year, $budget->month);
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
