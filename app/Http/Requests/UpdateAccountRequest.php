<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
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
            'id' => 'required|exists:accounts,id',
            'name' => [
                'required',
                Rule::unique('accounts', 'name')
                    ->where('deleted_at', null)
                    ->where('user_id', request()->user()->id)
                    ->ignore(request()->id)
            ],
            'amount' => 'required|numeric|min:0',
            'account_holder_id' => 'required|exists:account_holders,id',
        ];
    }
}
