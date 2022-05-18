<?php

namespace App\Http\Requests;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
                'required',
                Rule::unique('templates', 'name')->where('deleted_at', null)->where('user_id', request()->user()->id)
            ],
            'user_id' => 'required|exists:users,id',
            'accounts' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    $keys = array_keys($value);
                    if (Account::find($keys)?->count() != count($keys)) {
                        return $fail("Some of the $attribute listed don't exist.");
                    }
                }
            ],
            'accounts.*.amount' => 'required|numeric|min:0|max:10000000',
            'accounts.*.modifier' => [
                'required',
                'numeric',
                Rule::in([1, -1])  
            ],
        ];
    }
}
