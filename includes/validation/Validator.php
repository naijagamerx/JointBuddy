<?php
/**
 * Input Validator
 *
 * Provides consistent input validation and sanitization
 * Prevents SQL injection, XSS, and other input-based attacks
 *
 * @package CannaBuddy
 */

class ValidationException extends Exception {
    private array $errors = [];

    public function __construct(string $message, array $errors = []) {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function getFirstError(): string {
        return $this->errors[0] ?? $this->getMessage();
    }
}

class Validator {

    /**
     * Validate and sanitize a string
     *
     * @param mixed $value Value to validate
     * @param int $maxLen Maximum allowed length
     * @param bool $required Whether value is required
     * @return string Sanitized string
     * @throws ValidationException If validation fails
     */
    public static function string($value, int $maxLen = 255, bool $required = true): string {
        if ($required && empty($value)) {
            throw new ValidationException('Value is required');
        }

        if (!$required && empty($value)) {
            return '';
        }

        $value = trim((string) $value);

        if (strlen($value) > $maxLen) {
            throw new ValidationException("Value must not exceed {$maxLen} characters");
        }

        // Sanitize for HTML output
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Validate an email address
     *
     * @param mixed $value Value to validate
     * @param bool $required Whether value is required
     * @return string Validated email
     * @throws ValidationException If validation fails
     */
    public static function email(string $value, bool $required = true): string {
        if ($required && empty($value)) {
            throw new ValidationException('Email is required');
        }

        if (!$required && empty($value)) {
            return '';
        }

        $value = trim((string) $value);

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email address');
        }

        return $value;
    }

    /**
     * Validate an integer
     *
     * @param mixed $value Value to validate
     * @param int $min Minimum allowed value
     * @param int|null $max Maximum allowed value
     * @param bool $required Whether value is required
     * @return int Validated integer
     * @throws ValidationException If validation fails
     */
    public static function integer($value, int $min = 0, ?int $max = null, bool $required = true): int {
        if ($required && $value === '' && $value !== '0') {
            throw new ValidationException('Value is required');
        }

        if (!$required && $value === '') {
            return 0;
        }

        if (!is_numeric($value)) {
            throw new ValidationException('Value must be a number');
        }

        $value = (int) $value;

        if ($value < $min) {
            throw new ValidationException("Value must be at least {$min}");
        }

        if ($max !== null && $value > $max) {
            throw new ValidationException("Value must not exceed {$max}");
        }

        return $value;
    }

    /**
     * Validate a price/decimal number
     *
     * @param mixed $value Value to validate
     * @param bool $required Whether value is required
     * @return float Validated price
     * @throws ValidationException If validation fails
     */
    public static function price($value, bool $required = true): float {
        if ($required && $value === '' && $value !== '0') {
            throw new ValidationException('Price is required');
        }

        if (!$required && $value === '') {
            return 0.0;
        }

        if (!is_numeric($value)) {
            throw new ValidationException('Price must be a number');
        }

        $value = (float) $value;

        if ($value < 0) {
            throw new ValidationException('Price cannot be negative');
        }

        return round($value, 2);
    }

    /**
     * Validate a URL
     *
     * @param mixed $value Value to validate
     * @param bool $required Whether value is required
     * @return string Validated URL
     * @throws ValidationException If validation fails
     */
    public static function url(string $value, bool $required = true): string {
        if ($required && empty($value)) {
            throw new ValidationException('URL is required');
        }

        if (!$required && empty($value)) {
            return '';
        }

        $value = trim((string) $value);

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            throw new ValidationException('Invalid URL');
        }

        return $value;
    }

    /**
     * Validate a slug (URL-friendly string)
     *
     * @param mixed $value Value to validate
     * @param bool $required Whether value is required
     * @return string Validated slug
     * @throws ValidationException If validation fails
     */
    public static function slug(string $value, bool $required = true): string {
        if ($required && empty($value)) {
            throw new ValidationException('Slug is required');
        }

        if (!$required && empty($value)) {
            return '';
        }

        $value = trim((string) $value);
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9-]+/', '-', $value);
        $value = trim($value, '-');

        if (empty($value)) {
            throw new ValidationException('Slug cannot be empty');
        }

        return $value;
    }

    /**
     * Validate a phone number
     *
     * @param mixed $value Value to validate
     * @param bool $required Whether value is required
     * @return string|null Validated phone number or null
     * @throws ValidationException If validation fails
     */
    public static function phone($value, bool $required = false): ?string {
        if (!$required && empty($value)) {
            return null;
        }

        $value = trim((string) $value);
        // Remove all non-numeric characters
        $value = preg_replace('/[^0-9]/', '', $value);

        // Check length (South African numbers: 10 digits, international: up to 15)
        if (strlen($value) < 10 || strlen($value) > 15) {
            throw new ValidationException('Invalid phone number');
        }

        return $value;
    }

    /**
     * Validate boolean value
     *
     * @param mixed $value Value to validate
     * @param bool $default Default value if invalid
     * @return bool Validated boolean
     */
    public static function boolean($value, bool $default = false): bool {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) (int) $value;
        }

        if (is_string($value)) {
            $lower = strtolower($value);
            return in_array($lower, ['true', '1', 'yes', 'on'], true);
        }

        return $default;
    }

    /**
     * Validate an array value
     *
     * @param mixed $value Value to validate
     * @param bool $required Whether value is required
     * @return array Validated array
     * @throws ValidationException If validation fails
     */
    public static function array($value, bool $required = false): array {
        if (!$required && empty($value)) {
            return [];
        }

        if (!is_array($value)) {
            throw new ValidationException('Value must be an array');
        }

        return $value;
    }

    /**
     * Validate file upload
     *
     * @param array $file File from $_FILES array
     * @param array $allowedTypes Allowed MIME types
     * @param int $maxSize Maximum file size in bytes
     * @return array Validated file info
     * @throws ValidationException If validation fails
     */
    public static function file(array $file, array $allowedTypes = [], int $maxSize = 2097152): array {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new ValidationException('No file uploaded');
        }

        // Check size
        if ($file['size'] > $maxSize) {
            $maxMB = round($maxSize / 1048576, 2);
            throw new ValidationException("File size exceeds {$maxMB}MB limit");
        }

        // Check type
        if (!empty($allowedTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $allowedTypes, true)) {
                throw new ValidationException('Invalid file type');
            }
        }

        return $file;
    }

    /**
     * Sanitize HTML content (allow certain tags)
     *
     * @param mixed $value Value to sanitize
     * @param array $allowedTags Allowed HTML tags
     * @return string Sanitized HTML
     */
    public static function html($value, array $allowedTags = []): string {
        if (empty($value)) {
            return '';
        }

        $value = (string) $value;

        // If no allowed tags, escape everything
        if (empty($allowedTags)) {
            return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Allow specific tags
        $allowed = implode('', $allowedTags);
        $value = strip_tags($value, $allowed);

        return $value;
    }

    /**
     * Validate date
     *
     * @param mixed $value Value to validate
     * @param string $format Expected date format
     * @param bool $required Whether value is required
     * @return string|null Validated date or null
     * @throws ValidationException If validation fails
     */
    public static function date($value, string $format = 'Y-m-d', bool $required = true): ?string {
        if (!$required && empty($value)) {
            return null;
        }

        $value = trim((string) $value);
        $date = DateTime::createFromFormat($format, $value);

        if (!$date || $date->format($format) !== $value) {
            throw new ValidationException("Invalid date format, expected {$format}");
        }

        return $value;
    }

    /**
     * Validate enum value
     *
     * @param mixed $value Value to validate
     * @param array $allowedValues Allowed values
     * @param bool $required Whether value is required
     * @return string Validated enum value
     * @throws ValidationException If validation fails
     */
    public static function enum($value, array $allowedValues, bool $required = true): string {
        if ($required && empty($value)) {
            throw new ValidationException('Value is required');
        }

        if (!$required && empty($value)) {
            return $allowedValues[0] ?? '';
        }

        if (!in_array($value, $allowedValues, true)) {
            $allowed = implode(', ', $allowedValues);
            throw new ValidationException("Invalid value. Allowed: {$allowed}");
        }

        return $value;
    }

    /**
     * Validate text area content (longer text with line breaks)
     *
     * @param mixed $value Value to validate
     * @param int $maxLen Maximum allowed length
     * @param bool $required Whether value is required
     * @return string Validated text
     * @throws ValidationException If validation fails
     */
    public static function text($value, int $maxLen = 5000, bool $required = false): string {
        if ($required && empty($value)) {
            throw new ValidationException('Text is required');
        }

        if (!$required && empty($value)) {
            return '';
        }

        $value = trim((string) $value);

        if (strlen($value) > $maxLen) {
            throw new ValidationException("Text must not exceed {$maxLen} characters");
        }

        // Preserve line breaks but sanitize for HTML
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $value;
    }

    /**
     * Validate multiple values at once
     *
     * @param array $rules Validation rules ['field' => ['type', 'required' => true, 'param1' => value]]
     * @param array $data Data to validate (usually $_POST)
     * @return array Validated data
     * @throws ValidationException If any validation fails
     */
    public static function validate(array $rules, array $data): array {
        $validated = [];
        $errors = [];

        foreach ($rules as $field => $rule) {
            $type = $rule[0] ?? 'string';
            $required = $rule['required'] ?? false;

            try {
                $value = $data[$field] ?? null;

                switch ($type) {
                    case 'string':
                        $validated[$field] = self::string($value, $rule['max'] ?? 255, $required);
                        break;
                    case 'email':
                        $validated[$field] = self::email($value, $required);
                        break;
                    case 'integer':
                        $validated[$field] = self::integer($value, $rule['min'] ?? 0, $rule['max'] ?? null, $required);
                        break;
                    case 'price':
                        $validated[$field] = self::price($value, $required);
                        break;
                    case 'slug':
                        $validated[$field] = self::slug($value, $required);
                        break;
                    case 'boolean':
                        $validated[$field] = self::boolean($value, $rule['default'] ?? false);
                        break;
                    case 'text':
                        $validated[$field] = self::text($value, $rule['max'] ?? 5000, $required);
                        break;
                    case 'phone':
                        $validated[$field] = self::phone($value, $required);
                        break;
                    case 'array':
                        $validated[$field] = self::array($value, $required);
                        break;
                    case 'enum':
                        $validated[$field] = self::enum($value, $rule['values'] ?? [], $required);
                        break;
                    default:
                        $validated[$field] = $value;
                }
            } catch (ValidationException $e) {
                $errors[$field] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }

        return $validated;
    }
}
