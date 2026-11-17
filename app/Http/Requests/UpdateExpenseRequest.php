<?php

namespace App\Http\Requests;

use App\Concerns\ExpenseValidationRules;
use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
{
    use ExpenseValidationRules;

    public function authorize(): bool
    {
        $expense = $this->expense();

        return $expense !== null && $this->user()?->can('update', $expense) === true;
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

    private function expense(): ?Expense
    {
        $expense = $this->route('expense');

        return $expense instanceof Expense ? $expense : null;
    }
}
