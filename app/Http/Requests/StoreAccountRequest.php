<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
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
                Rule::unique('accounts', 'name')->where('deleted_at', null)->where('user_id', request()->user()->id),
            ],
            'note' => 'string|max:1000',
            'amount' => 'required|numeric|min:0',
            'user_id' => 'required|exists:users,id',
        ];
    }

    public function prepareForValidation() {
        $this->merge([
            'note' => (string) $this->input('note')
        ]);
    }
}
