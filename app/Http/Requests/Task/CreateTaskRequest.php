<?php

namespace App\Http\Requests\Task;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateTaskRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'header' => 'required|string|max:255',
            'description' => 'required|string',
            'status' => 'required|in:planned,in_progress,done',
            'completed_at' => 'nullable|date|after_or_equal:today',
            'user_id' => 'required|exists:users,id',
            'attachment' => 'nullable|file|max:10240',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Валидация не успешна',
                'errors' => $validator->errors()
            ], 422)
        );
    }

    public function messages(): array
    {
        return [
            'header.required' => 'Заголовок задачи обязателен для заполнения',
            'header.string' => 'Заголовок должен быть строкой',
            'header.max' => 'Заголовок не может превышать 255 символов',
            'description.required' => 'Описание задачи обязательно для заполнения',
            'description.string' => 'Описание должно быть строкой',
            'status.required' => 'Статус задачи обязателен',
            'status.in' => 'Статус должен быть одним из: planned, in_progress, done',
            'completed_at.date' => 'Дата завершения должна быть корректной датой',
            'completed_at.after_or_equal' => 'Дата завершения не может быть в прошлом',
            'user_id.required' => 'Исполнитель задачи обязателен',
            'user_id.exists' => 'Выбранный исполнитель не существует',
            'attachment.file' => 'Вложение должно быть файлом',
            'attachment.max' => 'Размер файла не должен превышать 10MB',
        ];
    }
}
