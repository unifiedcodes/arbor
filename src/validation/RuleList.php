<?php

namespace Arbor\validation;

use Arbor\validation\RuleListInterface;
use Arbor\validation\ValidationException;

/**
 * Provides a collection of validation methods for common data validation needs
 * 
 * @package Arbor\validation
 */
class RuleList implements RuleListInterface
{
    public function provides(): array
    {
        return [
            'string',
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
            'password',
            'callable',
            'fqn',
            'instanceOf',
            'isA'
        ];
    }

    /**
     * Validate string
     *
     * @param mixed $input
     * @return bool
     * @throws ValidationException
     */
    public function string($input): bool
    {
        if (!is_string($input)) {
            throw new ValidationException('must be a string');
        }

        return true;
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
            throw new ValidationException('must be a valid email address');
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
            throw new ValidationException('must be a valid URL');
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
            throw new ValidationException('must be an integer');
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
            throw new ValidationException('must be a valid float number');
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
            throw new ValidationException('must be a boolean value');
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
            throw new ValidationException('is required');
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
            throw new ValidationException("must be a string of at least {$min} characters");
        }

        if (mb_strlen($input) < $min) {
            throw new ValidationException("must be at least {$min} characters long");
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
            throw new ValidationException("must be a string of {$max} characters");
        }

        if (mb_strlen($input) > $max) {
            throw new ValidationException("must not exceed {$max} characters");
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
            throw new ValidationException("must be a string exactly {$length} characters long ");
        }

        if (mb_strlen($input) !== $length) {
            throw new ValidationException("must be exactly {$length} characters long");
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
            throw new ValidationException('must be numeric');
        }

        if ((float)$input < $min) {
            throw new ValidationException("must be at least {$min}");
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
            throw new ValidationException('must be numeric');
        }

        if ((float)$input > $max) {
            throw new ValidationException("must not exceed {$max}");
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
            throw new ValidationException("must be one of: {$allowedValues}");
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
            throw new ValidationException('must be a valid phone number');
        }

        // Remove common phone number separators
        $cleaned = preg_replace('/[\s\-\(\)\+\.]/', '', $input);

        // Check if it contains only digits and is between 10-15 characters
        if (preg_match('/^\d{10,15}$/', $cleaned) !== 1) {
            throw new ValidationException('must be a valid phone number');
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
            throw new ValidationException("must be a string of a valid Date in format {$format}");
        }

        $date = \DateTime::createFromFormat($format, $input);
        if (!$date || $date->format($format) !== $input) {
            throw new ValidationException("must be a valid date in format {$format}");
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
            throw new ValidationException("must be a valid{$type} IP address");
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
            throw new ValidationException('must be a valid JSON');
        }

        json_decode($input);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ValidationException('must be valid JSON');
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
            throw new ValidationException('must be a string in a valid UUID format');
        }

        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        if (preg_match($pattern, $input) !== 1) {
            throw new ValidationException('must be a valid UUID');
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
            throw new ValidationException('must be a string of alpha-numeric charachters only');
        }

        if (preg_match('/^[a-zA-Z0-9]+$/', $input) !== 1) {
            throw new ValidationException('must contain only letters and numbers');
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
            throw new ValidationException('must be a string of alphabet charachters only');
        }

        if (preg_match('/^[a-zA-Z]+$/', $input) !== 1) {
            throw new ValidationException('must contain only letters');
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
            throw new ValidationException('must be numeric');
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
            throw new ValidationException('must be a string of only digits');
        }

        if (preg_match('/^\d+$/', $input) !== 1) {
            throw new ValidationException('must contain only digits');
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
            throw new ValidationException('must be the same as the comparison value');
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
            throw new ValidationException('must be different from the comparison value');
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
            throw new ValidationException('must be an array');
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
            throw new ValidationException('must be a valid uploaded file');
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
            throw new ValidationException('must be a string of valid slug');
        }

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $input) !== 1) {
            throw new ValidationException('must be a valid slug (lowercase letters, numbers, and hyphens only)');
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
            throw new ValidationException('must be a string of valid password format');
        }

        if (preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $input) !== 1) {
            throw new ValidationException('must be at least 8 characters long and contain at least one lowercase letter, one uppercase letter, one digit, and one special character');
        }

        return true;
    }

    public function callable($input): bool
    {
        if (!is_callable($input)) {
            throw new ValidationException('must be a callable');
        }

        return true;
    }

    public function fqn($input): bool
    {
        if (!is_string($input)) {
            throw new ValidationException('must be a valid fully qualified class name');
        }

        if (preg_match('/^(?:\\\\?[A-Za-z_][A-Za-z0-9_]*)(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*$/', $input) !== 1) {
            throw new ValidationException('must be a valid fully qualified class name');
        }

        if (!class_exists($input)) {
            throw new ValidationException("class {$input} does not exist");
        }

        return true;
    }

    public function instanceOf($input, string $type): bool
    {
        if (!class_exists($type) && !interface_exists($type)) {
            throw new ValidationException(
                "class or interface {$type} does not exist"
            );
        }

        if (!$input instanceof $type) {
            throw new ValidationException(
                "must be an instance of {$type}"
            );
        }

        return true;
    }


    public function isA($input, string $type): bool
    {
        if (!is_string($input) || !class_exists($input)) {
            throw new ValidationException(
                'must be a valid class name'
            );
        }

        if (!class_exists($type) && !interface_exists($type)) {
            throw new ValidationException(
                "class or interface {$type} does not exist"
            );
        }

        if (!is_a($input, $type, true)) {
            throw new ValidationException(
                "class {$input} must extend or implement {$type}"
            );
        }

        return true;
    }
}
