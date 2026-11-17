<?php

namespace App\Http\Requests;

use App\Concerns\BudgetValidationRules;
use App\Models\Budget;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetRequest extends FormRequest
{
    use BudgetValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $budget = $this->budget();

        return $budget !== null && $this->user()?->can('update', $budget) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->budgetRules($this->budget());
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->budgetMessages();
    }

    private function budget(): ?Budget
    {
        $budget = $this->route('budget');

        return $budget instanceof Budget ? $budget : null;
    }
}
