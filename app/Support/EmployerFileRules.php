<?php

namespace App\Support;

class EmployerFileRules
{
    /**
     * @return list<string>
     */
    public static function document(): array
    {
        return [
            'required',
            'file',
            'max:5120',
            'mimes:pdf,doc,docx,jpg,jpeg,png',
            'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function documentMessages(string $field = 'file'): array
    {
        return [
            $field.'.mimes' => 'The company document must be a PDF, DOC, DOCX, JPG, or PNG file.',
            $field.'.mimetypes' => 'The company document must be a PDF, DOC, DOCX, JPG, or PNG file.',
            $field.'.max' => 'The company document must not be larger than 5 MB.',
        ];
    }

    /**
     * @return list<string>
     */
    public static function documentExtensions(): array
    {
        return ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    }
}
