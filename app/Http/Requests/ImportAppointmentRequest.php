<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('appointments.create') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'mimes:docx', 'mimetypes:application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/zip,application/octet-stream'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Escolha o arquivo da ficha de atendimento.',
            'file.file' => 'Não foi possível receber o arquivo. Tente novamente.',
            'file.uploaded' => 'Não foi possível receber o arquivo. Ele pode ser maior que o permitido.',
            'file.max' => 'O arquivo deve ter no máximo 10 MB.',
            'file.mimes' => 'Envie a ficha em Word (.docx). Se ela estiver em .doc ou PDF, abra no Word e use “Salvar como” → Documento do Word (.docx).',
            'file.mimetypes' => 'Envie a ficha em Word (.docx). Se ela estiver em .doc ou PDF, abra no Word e use “Salvar como” → Documento do Word (.docx).',
        ];
    }
}
