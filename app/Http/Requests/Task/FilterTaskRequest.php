<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class FilterTaskRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => 'nullable|in:planned,in_progress,done',
            'user_id' => 'nullable|exists:users,id',
            'completed_at' => 'nullable|date',
            'completed_from' => 'nullable|date',
            'completed_to' => 'nullable|date|after_or_equal:completed_from',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Статус должен быть одним из: planned, in_progress, done',
            'user_id.exists' => 'Выбранный исполнитель не существует',
            'completed_at.date' => 'Дата завершения должна быть корректной датой',
            'completed_from.date' => 'Дата начала периода должна быть корректной датой',
            'completed_to.date' => 'Дата окончания периода должна быть корректной датой',
            'completed_to.after_or_equal' => 'Дата окончания периода должна быть после или равна дате начала',
        ];
    }
}
