<?php

namespace App\Support;

class CandidateFileRules
{
    /**
     * @return list<string>
     */
    public static function resume(): array
    {
        return [
            'required',
            'file',
            'max:5120',
            'mimes:pdf,doc,docx',
            'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
    }

    /**
     * @return list<string>
     */
    public static function optionalResume(): array
    {
        $rules = self::resume();
        $rules[0] = 'nullable';

        return $rules;
    }

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
    public static function resumeMessages(string $field = 'file'): array
    {
        return [
            $field.'.mimes' => 'The resume must be a PDF, DOC, or DOCX file.',
            $field.'.mimetypes' => 'The resume must be a PDF, DOC, or DOCX file.',
            $field.'.max' => 'The resume must not be larger than 5 MB.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function documentMessages(string $field = 'file'): array
    {
        return [
            $field.'.mimes' => 'The document must be a PDF, DOC, DOCX, JPG, or PNG file.',
            $field.'.mimetypes' => 'The document must be a PDF, DOC, DOCX, JPG, or PNG file.',
            $field.'.max' => 'The document must not be larger than 5 MB.',
        ];
    }

    /**
     * @return list<string>
     */
    public static function resumeExtensions(): array
    {
        return ['pdf', 'doc', 'docx'];
    }

    /**
     * @return list<string>
     */
    public static function documentExtensions(): array
    {
        return ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    }
}
