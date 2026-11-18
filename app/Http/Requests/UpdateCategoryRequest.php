<?php

namespace App\Http\Requests;

use App\Concerns\CategoryValidationRules;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    use CategoryValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $category = $this->category();

        if ($category === null) {
            return false;
        }

        $shared = $this->has('shared')
            ? $this->boolean('shared')
            : $category->user_id === null;

        return $this->user()?->can('update', [$category, $shared]) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->categoryRules($this->category());
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->categoryMessages();
    }

    private function category(): ?Category
    {
        $category = $this->route('category');

        return $category instanceof Category ? $category : null;
    }
}
