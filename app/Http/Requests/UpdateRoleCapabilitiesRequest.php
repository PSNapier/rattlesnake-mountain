<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRoleCapabilitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'matrix' => ['required', 'array'],
            'matrix.*' => ['array'],
            'matrix.*.*' => ['string', Rule::in(Role::areas())],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $matrix = $this->input('matrix', []);

            if (! is_array($matrix)) {
                return;
            }

            $allowedRoles = array_map(
                static fn (Role $role): string => $role->value,
                Role::cases(),
            );

            foreach (array_keys($matrix) as $role) {
                if (! in_array($role, $allowedRoles, true)) {
                    $validator->errors()->add(
                        "matrix.{$role}",
                        'Unknown role.',
                    );
                }
            }

            $adminCapabilities = $matrix[Role::Admin->value] ?? null;

            if (is_array($adminCapabilities) && ! in_array('users', $adminCapabilities, true)) {
                $validator->errors()->add(
                    'matrix.admin',
                    'The Admin role must retain the users capability.',
                );
            }
        });
    }

    /**
     * @return array<string, list<string>>
     */
    public function matrix(): array
    {
        /** @var array<string, list<string>> $matrix */
        $matrix = $this->validated('matrix');

        return $matrix;
    }
}
