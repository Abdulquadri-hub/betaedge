<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class EnrollmentSubmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_type' => 'required|in:adult,parent',
            'student.name' => 'required|string|max:255',
            'student.email' => 'required|email|max:255',
            'student.password' => 'required|string|min:8|confirmed',
            'student.phone' => 'required|string|max:30',
            'student.date_of_birth' => 'required|date',
            'parent.name' => 'required_if:account_type,parent|string|max:255',
            'parent.email' => 'required_if:account_type,parent|email|max:255|different:student.email',
            'parent.phone' => 'required_if:account_type,parent|string|max:30',
            'parent.relationship' => 'required_if:account_type,parent|in:father,mother,guardian,grandmother,grandfather,uncle,aunt,other',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->sometimes(
            'student.date_of_birth',
            'before:' . now()->subYears(18)->toDateString(),
            fn () => $this->input('account_type') === 'adult'
        );

        $validator->sometimes(
            'student.date_of_birth',
            'after:' . now()->subYears(18)->toDateString(),
            fn () => $this->input('account_type') === 'parent'
        );
    }
}
