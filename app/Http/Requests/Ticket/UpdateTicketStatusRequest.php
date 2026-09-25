<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:new,assigned,in_progress,waiting_user,resolved,closed,escalated'],
            'comment' => ['nullable', 'string'],
            'solution' => ['nullable', 'string'],
            'diagnostic' => ['nullable', 'string'],
            'work_time_minutes' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
