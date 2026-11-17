<?php

namespace App\Http\Controllers;

use App\ExpenseType;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\User;
use App\Services\CategoryService;
use App\Services\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly ExpenseService $expenses,
        private readonly CategoryService $categories,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Expense::class);

        $filters = $this->filters($request);

        return Inertia::render('expenses/Index', [
            'expenses' => $this->expenses->listFor($this->user($request), $filters),
            'categories' => $this->categories->listFor($this->user($request)),
            'filters' => $filters,
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $this->expenses->create($this->user($request), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Gasto creado.']);

        return to_route('expenses.index', $this->filters($request));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->expenses->update($expense, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Gasto actualizado.']);

        return to_route('expenses.index', $this->filters($request));
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        Gate::authorize('delete', $expense);

        $this->expenses->delete($expense);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Gasto eliminado.']);

        return to_route('expenses.index', $this->filters($request));
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        /** @var array<string, mixed> $query */
        $query = array_filter(
            $request->query(),
            fn (mixed $value): bool => $value !== null && $value !== '',
        );

        $validated = Validator::make($query, [
            'month' => ['sometimes', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'type' => ['sometimes', Rule::enum(ExpenseType::class)],
            'category_id' => ['sometimes', 'integer'],
            'search' => ['sometimes', 'string', 'max:120'],
        ])->validate();

        $type = $validated['type'] ?? null;

        return [
            'month' => is_string($validated['month'] ?? null)
                ? $validated['month']
                : now()->format('Y-m'),
            'type' => $type instanceof ExpenseType ? $type->value : (is_string($type) ? $type : null),
            'category_id' => is_numeric($validated['category_id'] ?? null)
                ? (int) $validated['category_id']
                : null,
            'search' => is_string($validated['search'] ?? null) && trim($validated['search']) !== ''
                ? trim($validated['search'])
                : null,
        ];
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
