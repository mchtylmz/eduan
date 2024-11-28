<?php

namespace App\Http\Requests\Backend\Roles;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class CreateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return request()->user()->can('roles:add');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'permissions' => 'required|array'
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('Yetki Adı'),
            'permissions' => __('İşlem İzinleri')
        ];
    }

    public function failedAuthorization()
    {
        throw new AuthorizationException(
            message: __('Yetki eklenemez, yetkiniz bulunmuyor!')
        );
    }
}
