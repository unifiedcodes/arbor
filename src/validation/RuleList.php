<?php

namespace Arbor\validation;

use Arbor\contracts\validation\RuleListInterface;
use Arbor\validation\ValidationException;

/**
 * Provides a collection of validation methods for common data validation needs
 */
class RuleList implements RuleListInterface
{

    public function provides(): array
    {
        return [
            'int' => 'integer',
            'alnum' => 'alphanumeric',
            'al' => 'alpha',
            'num' => 'numeric',
            'email',
            'url',
            'integer',
            'float',
            'boolean',
            'required',
            'minLength',
            'maxLength',
            'length',
            'min',
            'max',
            'in',
            'phone',
            'date',
            'ip',
            'json',
            'uuid',
            'alphanumeric',
            'alpha',
            'numeric',
            'digits',
            'same',
            'different',
            'array',
            'file',
            'slug',
            'password'
        ];
    }

    /**
     * Validate email address
     *
     * @param mixed $input
     * @return bool
     * @throws ValidationException
     */
    public function email($input): bool
    {
        if (filter_var($input, FILTER_VALIDATE_EMAIL) === false) {
            throw new ValidationException('The Input : {input} must be a valid email address.');
        }
        return true;
    }

    /**
     * Validate URL
     *
     * @param mixed $input
     * @return bool
     * @throws ValidationException
     */
    public function url($input): bool
    {
        if (filter_var($input, FILTER_VALIDATE_URL) === false) {
            throw new ValidationException('The Input : {input} must be a valid URL.');
        }
        return true;
    }

    /**
     * Validate integer
     *
     * @param mixed $input
     * @return bool
     * @throws ValidationException
     */
    public function integer($input): bool
    {
        if (filter_var($input, FILTER_VALIDATE_INT) === false) {
            throw new ValidationException('The Input : {input} must be an integer.');
        }
        return true;
    }

    /**
     * Validate float/decimal number
     *
     * @param mixed $input
     * @return bool
     * @throws ValidationException
     */
    public function float($input): bool
    {
        if (filter_var($input, FILTER_VALIDATE_FLOAT) === false) {
            throw new ValidationException('The Input : {input} must be a valid float number.');
        }
        return true;
    }

    /**
     * Validate boolean
     *
     * @param mixed $input
     * @return bool
     * @throws ValidationException
     */
    public function boolean($input): bool
    {
        if (filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) {
            throw new ValidationException('The Input : {input} must be a boolean value.');
        }
        return true;
    }

    /**
     * Validate if input is not empty
     *
     * @param mixed $input
     * @return bool
     * @throws ValidationException
     */
    public function required($input): bool
    {
        $isEmpty = false;

        if (is_string($input)) {
            $isEmpty = trim($input) === '';
        } elseif (is_array($input)) {
            $isEmpty = empty($input);
        } else {
            $isEmpty = $input === null || $input === '';
        }

        if ($isEmpty) {
            throw new ValidationException('The Input : {input} is required.');
        }

        return true;
    }

    /**
     * Validate string length (minimum)
     *
     * @param mixed $input
     * @param int $min Minimum length
     * @return bool
     * @throws ValidationException
     */
    public function minLength($input, int $min = 1): bool
    {
        if (!is_string($input)) {
            throw new ValidationException('The Input : {input} must be a string.');
        }

        if (mb_strlen($input) < $min) {
            throw new ValidationException("The Input : {input} must be at least {$min} characters long.");
        }

        return true;
    }

    /**
     * Validate string length (maximum)
     *
     * @param mixed $input
     * @param int $max Maximum length
     * @return bool
     * @throws ValidationException
     */
    public function maxLength($input, int $max): bool
    {
        if (!is_string($input)) {
            throw new ValidationException('The Input : {input} must be a string.');
        }

        if (mb_strlen($input) > $max) {
            throw new ValidationException("The Input : {input} must not exceed {$max} characters.");
        }

        return true;
    }

    /**
     * Validate string length (exact)
     *
     * @param mixed $input
     * @param int $length Exact length required
     * @return bool
     * @throws ValidationException
     */
    public function length($input, int $length): bool
    {
        if (!is_string($input)) {
            throw new ValidationException('The Input : {input} must be a string.');
        }

        if (mb_strlen($input) !== $length) {
            throw new ValidationException("The Input : {input} must be exactly {$length} characters long.");
        }

        return true;
    }

    /**
     * Validate numeric value (minimum)
     *
     * @param mixed $input
     * @param float $min Minimum value
     * @return bool
     * @throws ValidationException
     */
    public function min($input, float $min): bool
    {
        if (!is_numeric($input)) {
            throw new ValidationException('The Input : {input} must be numeric.');
        }

        if ((float)$input < $min) {
            throw new ValidationException("The Input : {input} must be at least {$min}.");
        }

        return true;
    }

    /**
     * Validate numeric value (maximum)
     *
     * @param mixed $input
     * @param float $max Maximum value
     * @return bool
     * @throws ValidationException
     */
    public function max($input, float $max): bool
    {
        if (!is_numeric($input)) {
            throw new ValidationException('The Input : {input} must be numeric.');
        }

        if ((float)$input > $max) {
            throw new ValidationException("The Input : {input} must not exceed {$max}.");
        }

        return true;
    }

    /**
     * Validate if input is in allowed values array
     *
     * @param mixed $input
     * @param array $allowed Array of allowed values
     * @return bool
     * @throws ValidationException
     */
    public function in($input, array $allowed): bool
    {
        if (!in_array($input, $allowed, true)) {
            $allowedValues = implode(', ', array_map(function ($val) {
                return is_string($val) ? "'{$val}'" : $val;
            }, $allowed));
            throw new ValidationException("The Input : {input} must be one of: {$allowedValues}.");
        }

        return true;
    }

    /**
     * Validate phone number (basic format)
     *
     * @param mixed $input
     * @return bool
     * @throws ValidationException
     */
    public function phone($input): bool
    {
        if (!is_string($input)) {
            throw new ValidationException('The Input : {input} must be a string.');
        }

        // Remove common phone number separators
        $cleaned = preg_replace('/[\s\-\(\)\+\.]/', '', $input);

        // Check if it contains only digits and is between 10-15 characters
        if (preg_match('/^\d{10,15}$/', $cleaned) !== 1) {
            throw new ValidationException('The Input : {input} must be a valid phone number.');
        }

        return true;
    }

    /**
     * Validate date format
     *
     * @param mixed $input
     * @param string $format Date format (default: Y-m-d)
     * @return bool
     * @throws ValidationException
     */
    public function date($input, string $format = 'Y-m-d'): bool
    {
        if (!is_string($input)) {
            throw new ValidationException('The Input : {input} must be a string.');
        }

        $date = \DateTime::createFromFormat($format, $input);
        if (!$date || $date->format($format) !== $input) {
            throw new ValidationException("The Input : {input} must be a valid date in format {$format}.");
        }

        return true;
    }

    /**
     * Validate IP address
     *
     * @param mixed $input
     * @param int $flags FILTER_FLAG_IPV4 or FILTER_FLAG_IPV6 or both
     * @return bool
     * @throws ValidationException
     */
    public function ip($input, int $flags = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6): bool
    {
        if (filter_var($input, FILTER_VALIDATE_IP, $flags) === false) {
            $type = '';
            if ($flags === FILTER_FLAG_IPV4) {
                $type = ' IPv4';
            } elseif ($flags === FILTER_FLAG_IPV6) {
                $type = ' IPv6';
            }
            throw new ValidationException("The Input : {input} must be a valid{$type} IP address.");
        }

        return true;
    }

    /**
     * Validate JSON string
     *
     * @param mixed $input
     * @return bool
     * @throws ValidationException
     */
    public function json($input): bool
    {
        if (!is_string($input)) {
            throw new ValidationException('The Input : {input} must be a string.');
        }

        json_decode($input);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ValidationException('The Input : {input} must be valid JSON.');
        }

        return true;
    }

    /**
     * Validate UUID format
     *
     * @param mixed $input
     * @return bool
     * @throws ValidationException
     */
    public function uuid($input): bool
    {
        if (!is_string($input)) {
            throw new ValidationException('The Input : {input} must be a string.');
        }

        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        if (preg_match($pattern, $input) !== 1) {
            throw new ValidationException('The Input : {input} must be a valid UUID.');
        }

        return true;
    }

    /**
     * Validate alphanumeric string
     *
     * @param mixed $input
     * @return bool
     * @throws ValidationException
     */
    public function alphanumeric($input): bool
    {
        if (!is_string($input)) {
            throw new ValidationException('The Input : {input} must be a string.');
        }

        if (preg_match('/^[a-zA-Z0-9]+$/', $input) !== 1) {
            throw new ValidationException('The Input : {input} must contain only letters and numbers.');
        }

        return true;
    }

    /**
     * Validate alphabetic string (letters only)
     *
     * @param mixed $input
     * @return bool
     * @throws ValidationException
     */
    public function alpha($input): bool
    {
        if (!is_string($input)) {
            throw new ValidationException('The Input : {input} must be a string.');
        }

        if (preg_match('/^[a-zA-Z]+$/', $input) !== 1) {
            throw new ValidationException('The Input : {input} must contain only letters.');
        }

        return true;
    }

    /**
     * Validate numeric string
     *
     * @param mixed $input
     * @return bool
     * @throws ValidationException
     */
    public function numeric($input): bool
    {
        if (!is_numeric($input)) {
            throw new ValidationException('The Input : {input} must be numeric.');
        }

        return true;
    }

    /**
     * Validate string contains only digits
     *
     * @param mixed $input
     * @return bool
     * @throws ValidationException
     */
    public function digits($input): bool
    {
        if (!is_string($input)) {
            throw new ValidationException('The Input : {input} must be a string.');
        }

        if (preg_match('/^\d+$/', $input) !== 1) {
            throw new ValidationException('The Input : {input} must contain only digits.');
        }

        return true;
    }

    /**
     * Validate that two values are the same
     *
     * @param mixed $input
     * @param mixed $other
     * @return bool
     * @throws ValidationException
     */
    public function same($input, $other): bool
    {
        if ($input !== $other) {
            throw new ValidationException('The Input : {input} must be the same as the comparison value.');
        }

        return true;
    }

    /**
     * Validate that two values are different
     *
     * @param mixed $input
     * @param mixed $other
     * @return bool
     * @throws ValidationException
     */
    public function different($input, $other): bool
    {
        if ($input === $other) {
            throw new ValidationException('The Input : {input} must be different from the comparison value.');
        }

        return true;
    }

    /**
     * Validate that input is an array
     *
     * @param mixed $input
     * @return bool
     * @throws ValidationException
     */
    public function array($input): bool
    {
        if (!is_array($input)) {
            throw new ValidationException('The Input : {input} must be an array.');
        }

        return true;
    }

    /**
     * Validate that input is an uploaded file
     *
     * @param mixed $input
     * @return bool
     * @throws ValidationException
     */
    public function file($input): bool
    {
        if (!is_array($input) || !isset($input['tmp_name']) || !is_uploaded_file($input['tmp_name'])) {
            throw new ValidationException('The Input : {input} must be a valid uploaded file.');
        }

        return true;
    }

    /**
     * Validate slug format (lowercase letters, numbers, and hyphens)
     *
     * @param mixed $input
     * @return bool
     * @throws ValidationException
     */
    public function slug($input): bool
    {
        if (!is_string($input)) {
            throw new ValidationException('The Input : {input} must be a string.');
        }

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $input) !== 1) {
            throw new ValidationException('The Input : {input} must be a valid slug (lowercase letters, numbers, and hyphens only).');
        }

        return true;
    }

    /**
     * Validate password strength
     * Must contain at least: 1 lowercase, 1 uppercase, 1 digit, 1 special character, and be at least 8 characters
     *
     * @param mixed $input
     * @return bool
     * @throws ValidationException
     */
    public function password($input): bool
    {
        if (!is_string($input)) {
            throw new ValidationException('The Input : {input} must be a string.');
        }

        if (preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $input) !== 1) {
            throw new ValidationException('The Input : {input} must be at least 8 characters long and contain at least one lowercase letter, one uppercase letter, one digit, and one special character.');
        }

        return true;
    }
}
