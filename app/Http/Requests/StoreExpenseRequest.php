<?php

namespace App\Http\Requests;

use App\Concerns\ExpenseValidationRules;
use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    use ExpenseValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Expense::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->expenseRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->expenseMessages();
    }
}
