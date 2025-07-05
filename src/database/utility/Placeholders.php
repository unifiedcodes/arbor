<?php

namespace Arbor\database\utility;

use RuntimeException;

/**
 * Placeholders - A performance biased SQL placeholders parser
 * 
 * This class parses SQL statements to extract named placeholders and their optional type hints
 * while properly handling SQL comments and string literals. It can work with both named placeholders
 * (:parameter) and positional placeholders (?), but not mixed together in the same query.
 * 
 * The parser also supports type hints attached to placeholders in the format:
 * - Named: ":paramName@type"
 * - Positional: "?@type"
 * 
 * These type hints are extracted and stored separately from the SQL that will be sent to PDO.
 * 
 * @package Arbor\database
 * 
 */
class Placeholders
{
    /**
     * The SQL statement being parsed
     * 
     * @var string
     */
    protected string $sql;

    /**
     * Length of the SQL string
     * 
     * @var int
     */
    protected int $length;

    /**
     * Current position in the SQL string during parsing
     * 
     * @var int
     */
    protected int $pos = 0;

    /**
     * Extracted placeholders with their types
     * For named placeholders: [paramName => type]
     * For positional placeholders: [0 => type1, 1 => type2, ...]
     * Types will be null if no type hint was provided
     * 
     * @var array<string|int, string|null>
     */
    protected array $placeholders = [];

    /**
     * The detected placeholder type used in the SQL
     * Will be 'named', 'positional', or null if not yet determined
     * 
     * @var string|null
     */
    protected ?string $placeholderType = null;


    protected int $placeholderFound = 1;


    public function reset(): void
    {
        $this->sql = '';
        $this->length = 0;
        $this->pos = 0;
        $this->placeholders = [];
        $this->placeholderType = null;
        $this->placeholderFound = 1; // Reset counter for positional parameters
    }

    /**
     * Parse an SQL statement to extract named placeholders and their type hints
     * 
     * This method scans through the SQL, handling string literals and comments properly,
     * and extracts any placeholders along with their optional type hints. It returns
     * a potentially modified SQL string with type hints removed, while storing the 
     * placeholders and their types internally.
     * 
     * @param string $sql The SQL statement to parse
     * @return string The processed SQL with type hints removed (if any were present)
     */
    public function parseSql(string $sql): string
    {
        // Initialize parser state
        $this->sql = $sql;
        $this->placeholderType = null;
        $this->length = strlen($sql);
        $this->pos = 0;
        $this->placeholders = [];

        // Buffer for building the new SQL string
        $newSql = '';
        $lastCopiedPos = 0;

        // Main parsing loop
        while ($this->pos < $this->length) {
            $char = $sql[$this->pos];

            // Handle whitespace - faster direct check than ctype_space()
            if ($char === ' ' || $char === "\t" || $char === "\n" || $char === "\r") {
                $this->pos++;
                continue;
            }

            // Handle single-line comments
            if ($char === '-' && $this->peek(1) === '-') {
                $this->consumeSingleLineComment();
                continue;
            }

            if ($char === '#') {
                $this->consumeSingleLineComment();
                continue;
            }

            // Handle multi-line comments
            if ($char === '/' && $this->peek(1) === '*') {
                $this->consumeMultiLineComment();
                continue;
            }

            // Handle string literals
            if ($char === '\'' || $char === '"') {
                $this->consumeStringLiteral($char);
                continue;
            }

            // Process named placeholders
            if ($char === ':') {
                // Copy everything from last position to current position
                $newSql .= substr($sql, $lastCopiedPos, $this->pos - $lastCopiedPos);

                // Process parameter and get the SQL fragment to use
                $placeholderResult = $this->processPlaceholder();
                $newSql .= $placeholderResult['sqlFragment'];

                // Update the position for next copy operation
                $lastCopiedPos = $this->pos;
                continue;
            }

            if ($char === '?') {
                $newSql .= substr($sql, $lastCopiedPos, $this->pos - $lastCopiedPos);

                $this->processPositionalPlaceholder();

                // Retain just '?' in output SQL, stripping @type if present
                $newSql .= '?';

                $lastCopiedPos = $this->pos;
                continue;
            }

            // Move to next character
            $this->pos++;
        }

        // Add any remaining SQL text
        if ($lastCopiedPos < $this->length) {
            $newSql .= substr($sql, $lastCopiedPos);
        }

        // Return the extracted placeholders and possibly modified SQL
        return $lastCopiedPos > 0 ? $newSql : $sql;  // Only use new SQL if we made changes
    }

    /**
     * Look ahead in the SQL string without advancing the position
     * 
     * @param int $offset Number of characters to look ahead
     * @return string|null The character at the offset position, or null if out of bounds
     */
    protected function peek(int $offset): ?string
    {
        $index = $this->pos + $offset;
        return $index < $this->length ? $this->sql[$index] : null;
    }

    /**
     * Skip over a single-line comment (-- or # style)
     * 
     * Advances the position pointer to the character after the end of the comment.
     * 
     * @return void
     */
    protected function consumeSingleLineComment(): void
    {
        // Skip characters until we reach end of line or end of input
        do {
            $this->pos++;
        } while ($this->pos < $this->length && $this->sql[$this->pos] !== "\n");

        // Skip the newline character if not at end of input
        if ($this->pos < $this->length) {
            $this->pos++;
        }
    }

    /**
     * Skip over a multi-line comment (/*)
     * 
     * Advances the position pointer to the character after the end of the comment.
     * 
     * @return void
     */
    protected function consumeMultiLineComment(): void
    {
        $this->pos += 2; // Skip '/*'

        // Continue until we find the end of the comment or reach the end of input
        while ($this->pos < $this->length) {
            if ($this->sql[$this->pos] === '*' && $this->peek(1) === '/') {
                $this->pos += 2; // Skip '*/'
                break;
            }
            $this->pos++;
        }
    }

    /**
     * Skip over a string literal, handling escaped characters
     * 
     * Advances the position pointer to the character after the closing delimiter.
     * 
     * @param string $delimiter The quote character (' or ")
     * @return void
     */
    protected function consumeStringLiteral(string $delimiter): void
    {
        $this->pos++; // Skip opening quote

        // Continue until we find the matching closing quote or reach the end of input
        while ($this->pos < $this->length) {
            $char = $this->sql[$this->pos];

            // Handle escaped characters
            if ($char === '\\') {
                $this->pos += 2; // Skip the backslash and the escaped character
                continue;
            }

            // Check for closing quote
            if ($char === $delimiter) {
                $this->pos++; // Skip closing quote
                break;
            }

            $this->pos++;
        }
    }

    /**
     * Process a named placeholder and return the SQL fragment to use in its place
     * 
     * This method extracts the placeholder name and optional type hint,
     * storing the type information and returning the placeholder for the processed SQL.
     * 
     * @return array{sqlFragment: string} Contains 'sqlFragment' to use in the constructed SQL
     * @throws RuntimeException If mixing placeholder types (named and positional)
     */
    protected function processPlaceholder(): array
    {
        // if positional placeholder exists.
        if ($this->placeholderType === 'positional') {
            throw new RuntimeException("PDO doesn't allow mixing of placeholder types");
        }

        // update placeholder type.
        $this->placeholderType = 'named';

        $start = $this->pos + 1; // Skip ':'
        $end = $start;
        $sql = $this->sql;

        // Capture placeholder name using fast lookup table
        while ($end < $this->length) {
            $char = $sql[$end];
            if ($this->isValidIdentifierChar($char)) {
                $end++;
            } else {
                break;
            }
        }

        // Extract the placeholder name
        $placeholderName = substr($sql, $start, $end - $start);
        $this->pos = $end;

        // Prepare the SQL fragment to return (the placeholder name)
        $sqlFragment = ':' . $placeholderName;

        // Check for and process any type hint
        if ($this->peek(0) === '@') {
            $this->pos++; // Skip '@'
            $type = $this->captureType();

            $this->placeholders[$placeholderName] = $type;
        } else {
            // No type hint found
            $this->placeholders[$placeholderName] = null;
        }

        return [
            'sqlFragment' => $sqlFragment
        ];
    }

    /**
     * Capture a type hint following a placeholder
     * 
     * @return string The captured type name
     */
    protected function captureType(): string
    {
        $start = $this->pos;

        while ($this->pos < $this->length && $this->isValidIdentifierChar($this->sql[$this->pos])) {
            $this->pos++;
        }

        return substr($this->sql, $start, $this->pos - $start);
    }

    /**
     * Process a positional placeholder (?) and any optional type hint
     * 
     * @return void
     * @throws RuntimeException If mixing placeholder types (named and positional)
     */
    protected function processPositionalPlaceholder(): void
    {
        if ($this->placeholderType === 'named') {
            throw new RuntimeException("PDO doesn't allow mixing of placeholder types");
        }

        $this->placeholderType = 'positional';

        $type = null;
        $this->pos++; // Skip '?'

        // Handle type hint if present
        if ($this->peek(0) === '@') {
            $this->pos++; // Skip '@'
            $type = $this->captureType();
        }

        // Append placeholder with or without type
        $this->placeholders[$this->placeholderFound++] = $type;
    }

    /**
     * Check if a character is valid in an SQL identifier
     * 
     * Valid characters include letters, numbers, and underscores.
     * 
     * @param string $char Character to check
     * @return bool True if the character is valid for an SQL identifier
     */
    protected function isValidIdentifierChar(string $char): bool
    {
        // Fast path for lowercase letters (most common case)
        if ($char >= 'a' && $char <= 'z') {
            return true;
        }

        // Additional checks for other valid characters
        return ($char >= 'A' && $char <= 'Z') ||
            ($char >= '0' && $char <= '9') ||
            $char === '_';
    }

    /**
     * Get the extracted placeholders and their types
     * 
     * For named placeholders: [paramName => type]
     * For positional placeholders: [0 => type1, 1 => type2, ...]
     * Types will be null if no type hint was provided
     * 
     * @return array<string|int, string|null> Array of placeholders and their types
     */
    public function getPlaceholders(): array
    {
        return $this->placeholders;
    }

    /**
     * Get the type of placeholders used in the SQL
     * 
     * @return string|null 'named', 'positional', or null if no placeholders were found
     */
    public function getPlaceholderType(): ?string
    {
        return $this->placeholderType;
    }
}
