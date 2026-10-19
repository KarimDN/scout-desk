<?php

namespace Tests\Feature\Shared;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TranslationFilesTest extends TestCase
{
    /**
     * Validation rules the application uses. When a task starts using a new
     * rule, add it here and add its Arabic message to lang/ar/validation.php.
     */
    private const RULES_IN_USE = [
        'accepted', 'after_or_equal', 'array', 'confirmed', 'date', 'date_format',
        'email', 'exists', 'file', 'in', 'integer',
        'max.array', 'max.file', 'max.numeric', 'max.string',
        'mimes',
        'min.array', 'min.file', 'min.numeric', 'min.string',
        'numeric', 'required', 'string', 'unique', 'uploaded',
    ];

    public function test_ui_files_have_identical_keys(): void
    {
        $en = array_keys(Arr::dot(require lang_path('en/ui.php')));
        $ar = array_keys(Arr::dot(require lang_path('ar/ui.php')));

        sort($en);
        sort($ar);

        $this->assertSame($en, $ar);
    }

    public function test_arabic_ui_strings_are_not_empty(): void
    {
        foreach (Arr::dot(require lang_path('ar/ui.php')) as $key => $value) {
            $this->assertIsString($value, "ui.$key must be a string");
            $this->assertNotSame('', trim($value), "ui.$key is empty in Arabic");
        }
    }

    public function test_arabic_validation_covers_the_rules_in_use(): void
    {
        $ar = Arr::dot(require lang_path('ar/validation.php'));

        foreach (self::RULES_IN_USE as $rule) {
            $this->assertArrayHasKey($rule, $ar, "Missing Arabic validation message: $rule");
            $this->assertNotSame('', trim($ar[$rule]));
        }
    }

    public function test_arabic_validation_placeholders_match_english(): void
    {
        $en = Arr::dot(require lang_path('en/validation.php'));
        $ar = Arr::dot(require lang_path('ar/validation.php'));

        foreach (self::RULES_IN_USE as $rule) {
            $this->assertArrayHasKey($rule, $en, "Unexpected: no English message for $rule");

            $this->assertSame(
                $this->placeholders($en[$rule]),
                $this->placeholders($ar[$rule]),
                "Placeholders differ for rule: $rule"
            );
        }
    }

    public function test_arabic_validation_message_is_returned_in_arabic(): void
    {
        app()->setLocale('ar');

        $validator = Validator::make(['email' => ''], ['email' => 'required']);

        $this->assertSame('حقل البريد الإلكتروني مطلوب.', $validator->errors()->first('email'));
    }

    /**
     * @return list<string>
     */
    private function placeholders(string $message): array
    {
        preg_match_all('/:[a-z_]+/i', $message, $matches);

        $found = array_values(array_unique($matches[0]));
        sort($found);

        return $found;
    }
}
