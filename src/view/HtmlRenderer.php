<?php

namespace Arbor\view;

use Arbor\view\Html;

/**
 * Renders complete HTML documents with DOCTYPE, head, and body sections.
 * Handles meta tags, stylesheets, scripts, and HTML attributes with proper escaping.
 */
final class HtmlRenderer
{
    /**
     * Constructor for the HtmlRenderer.
     *
     * @param Html $html The HTML configuration object containing metadata and attributes.
     * @param string $body The rendered body content to include in the document.
     */
    public function __construct(
        private Html $html,
        private string $body,
    ) {}


    /**
     * Renders the complete HTML document.
     *
     * @return string The full HTML document with DOCTYPE declaration.
     */
    public function render(): string
    {
        return "<!DOCTYPE html>\n"
            . $this->openHtml()
            . $this->head()
            . $this->body()
            . "</html>";
    }


    /**
     * Renders the opening HTML tag with lang and custom attributes.
     *
     * @return string The rendered opening HTML tag.
     */
    private function openHtml(): string
    {
        $attributes = array_merge(
            ['lang' => $this->html->lang()],
            $this->html->htmlAttributes()
        );

        return "<html" . $this->renderAttributes($attributes) . ">\n";
    }


    /**
     * Renders the complete head section with meta tags, stylesheets, and scripts.
     *
     * @return string The rendered head section.
     */
    private function head(): string
    {
        return "<head>\n"
            . $this->metaCharset()
            . $this->base()
            . $this->meta()
            . $this->title()
            . $this->links()
            . $this->styles()
            . $this->inlineStyles()
            . $this->importMap()
            . $this->renderScripts('head')
            . $this->renderInlineScripts('head')
            . "</head>\n";
    }


    /**
     * Renders the body section with content and scripts.
     *
     * @return string The rendered body section.
     */
    private function body(): string
    {
        $attributes = $this->renderAttributes(
            $this->html->bodyAttributes()
        );

        return "<body{$attributes}>\n"
            . $this->body
            . "\n"
            . $this->renderScripts('body')
            . $this->renderInlineScripts('body')
            . $this->renderInitScripts()
            . "</body>\n";
    }


    /**
     * Renders the meta charset tag.
     *
     * @return string The rendered meta charset tag.
     */
    private function metaCharset(): string
    {
        $charset = $this->escape($this->html->charset());

        return "<meta charset=\"{$charset}\">\n";
    }


    /**
     * Renders the page title tag if a title is set.
     *
     * @return string The rendered title tag or empty string if not set.
     */
    private function title(): string
    {
        $title = $this->html->title();

        if ($title === null) {
            return '';
        }

        return "<title>"
            . $this->escape($title)
            . "</title>\n";
    }


    /**
     * Renders all external stylesheet link tags with optional nonce attribute.
     *
     * @return string The rendered stylesheet link tags.
     */
    private function styles(): string
    {
        $output = '';
        $nonce  = $this->html->nonce();

        foreach ($this->html->styles() as $style) {
            $href = $this->escape($style['href']);

            $attributes = $style['attributes'] ?? [];

            if ($nonce !== null) {
                $attributes['nonce'] = $nonce;
            }

            $output .= "<link rel=\"stylesheet\" href=\"{$href}\""
                . $this->renderAttributes($attributes)
                . ">\n";
        }

        return $output;
    }


    /**
     * Renders all inline style tags with optional nonce attribute.
     *
     * @return string The rendered inline style tags.
     */
    private function inlineStyles(): string
    {
        $output = '';
        $nonce  = $this->html->nonce();

        foreach ($this->html->inlineStyles() as $style) {
            $content = $style['content'] ?? '';
            $attributes = $style['attributes'] ?? [];

            if ($nonce !== null) {
                $attributes['nonce'] = $nonce;
            }

            $output .= "<style"
                . $this->renderAttributes($attributes)
                . ">\n"
                . $content
                . "\n</style>\n";
        }

        return $output;
    }


    /**
     * Converts an associative array of attributes into HTML attribute string.
     * Handles boolean attributes, null values, and proper escaping.
     *
     * @param array $attributes Associative array of attribute names and values.
     * @return string The rendered attributes string.
     */
    private function renderAttributes(array $attributes): string
    {
        $html = '';

        foreach ($attributes as $name => $value) {
            $name = $this->escape($name);

            if (is_bool($value)) {
                if ($value) {
                    $html .= " {$name}";
                }
                continue;
            }

            if ($value === null) {
                continue;
            }

            $html .= " {$name}=\""
                . $this->escape((string) $value)
                . "\"";
        }

        return $html;
    }


    /**
     * Escapes a string for safe use in HTML content.
     *
     * @param string $value The string to escape.
     * @return string The escaped string.
     */
    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }


    /**
     * Renders the base tag if a base href is set.
     *
     * @return string The rendered base tag or empty string if not set.
     */
    private function base(): string
    {
        $href = $this->html->baseHref();

        if ($href === null) {
            return '';
        }

        return "<base href=\""
            . $this->escape($href)
            . "\">\n";
    }


    /**
     * Renders all meta tags.
     *
     * @return string The rendered meta tags.
     */
    private function meta(): string
    {
        $output = '';

        foreach ($this->html->meta() as $meta) {
            $attributes = $this->renderAttributes($meta);
            $output .= "<meta{$attributes}>\n";
        }

        return $output;
    }


    /**
     * Renders all link tags (excluding stylesheets).
     *
     * @return string The rendered link tags.
     */
    private function links(): string
    {
        $output = '';

        foreach ($this->html->links() as $link) {
            $attributes = $this->renderAttributes($link);
            $output .= "<link{$attributes}>\n";
        }

        return $output;
    }


    /**
     * Renders external script tags for a specific placement (head or body).
     * Automatically adds nonce attribute if configured.
     *
     * @param string $placement The script placement: 'head' or 'body'.
     * @return string The rendered script tags.
     */
    private function renderScripts(string $placement): string
    {
        $output = '';
        $nonce  = $this->html->nonce();

        $scriptsByPlacement = $this->html->scripts();

        if (!isset($scriptsByPlacement[$placement])) {
            return '';
        }

        foreach ($scriptsByPlacement[$placement] as $script) {

            $attributes = $script['attributes'] ?? [];
            $attributes['src'] = $script['src'];

            if ($nonce !== null) {
                $attributes['nonce'] = $nonce;
            }

            $output .= "<script"
                . $this->renderAttributes($attributes)
                . "></script>\n";
        }

        return $output;
    }


    /**
     * Renders inline script tags for a specific placement (head or body).
     * Automatically adds nonce attribute if configured.
     *
     * @param string $placement The script placement: 'head' or 'body'.
     * @return string The rendered inline script tags.
     */
    private function renderInlineScripts(string $placement): string
    {
        $output = '';
        $nonce  = $this->html->nonce();

        $scriptsByPlacement = $this->html->inlineScripts();

        if (!isset($scriptsByPlacement[$placement])) {
            return '';
        }

        foreach ($scriptsByPlacement[$placement] as $script) {

            $content = $script['content'] ?? '';
            $attributes = $script['attributes'] ?? [];

            if ($nonce !== null) {
                $attributes['nonce'] = $nonce;
            }

            $output .= "<script"
                . $this->renderAttributes($attributes)
                . ">\n"
                . $content
                . "\n</script>\n";
        }

        return $output;
    }


    /**
     * Render all initialization modules in a single module script.
     *
     * @return string
     */
    private function renderInitScripts(): string
    {
        $initScripts = $this->html->initScripts();

        if ($initScripts === []) {
            return '';
        }

        $imports = [];
        $scripts = [];
        $usedNames = [];

        /*
     * First pass:
     * Parse names, validate identifiers and generate imports.
     */
        foreach ($initScripts as $index => $initScript) {

            $parts = preg_split(
                '/\s+as\s+/',
                trim($initScript['name']),
                2
            );

            $className = trim($parts[0]);

            $variableName = isset($parts[1]) && trim($parts[1]) !== ''
                ? trim($parts[1])
                : strtolower($className);

            $this->assertJsIdentifier($className);
            $this->assertJsIdentifier($variableName);

            /*
         * Class names and variable names share the same
         * JavaScript module scope.
         */
            foreach ([$className, $variableName] as $identifier) {

                if (isset($usedNames[$identifier])) {
                    throw new \RuntimeException(
                        "Conflicting initialization identifier '{$identifier}'."
                    );
                }

                $usedNames[$identifier] = true;
            }

            $dependencies = $initScript['dependencies'] ?? [];

            if (!is_array($dependencies)) {
                throw new \RuntimeException(
                    "Dependencies for '{$variableName}' must be an array."
                );
            }

            foreach ($dependencies as $dependency) {

                if (!is_string($dependency)) {
                    throw new \RuntimeException(
                        "Dependencies for '{$variableName}' must be strings."
                    );
                }

                $this->assertJsIdentifier($dependency);
            }

            $file = json_encode(
                $initScript['file'],
                JSON_HEX_TAG
                    | JSON_HEX_AMP
                    | JSON_HEX_APOS
                    | JSON_HEX_QUOT
                    | JSON_THROW_ON_ERROR
            );

            $imports[] = "import {$className} from {$file};";

            $scripts[$variableName] = [
                'class' => $className,
                'dependencies' => array_values(array_unique($dependencies))
            ];
        }

        /*
     * Second pass:
     * Resolve initialization order.
     */
        $ordered = [];
        $resolving = [];
        $resolved = [];

        $resolve = function (string $variableName) use (
            &$resolve,
            &$ordered,
            &$resolving,
            &$resolved,
            $scripts
        ): void {

            if (isset($resolved[$variableName])) {
                return;
            }

            if (isset($resolving[$variableName])) {
                throw new \RuntimeException(
                    "Circular initialization dependency involving '{$variableName}'."
                );
            }

            if (!isset($scripts[$variableName])) {
                throw new \RuntimeException(
                    "Unknown initialization dependency '{$variableName}'."
                );
            }

            $resolving[$variableName] = true;

            foreach ($scripts[$variableName]['dependencies'] as $dependency) {
                $resolve($dependency);
            }

            unset($resolving[$variableName]);

            $resolved[$variableName] = true;

            $ordered[] = $variableName;
        };

        foreach (array_keys($scripts) as $variableName) {
            $resolve($variableName);
        }

        /*
     * Third pass:
     * Generate constructor calls.
     */
        $initializations = [];

        foreach ($ordered as $variableName) {

            $script = $scripts[$variableName];

            $arguments = [];

            foreach ($script['dependencies'] as $dependency) {
                $arguments[] = "{$dependency}: {$dependency}";
            }

            $constructorArguments = $arguments
                ? "{" . implode(', ', $arguments) . "}"
                : '';

            $initializations[] = "var {$variableName} = new {$script['class']}({$constructorArguments});";
        }

        /*
     * Render the module script.
     */
        $attributes = [
            'type' => 'module'
        ];

        if ($this->html->nonce() !== null) {
            $attributes['nonce'] = $this->html->nonce();
        }

        $output = "<script"
            . $this->renderAttributes($attributes)
            . ">\n";

        $output .= implode("\n", $imports) . "\n\n";

        $output .= implode("\n", $initializations) . "\n";

        $output .= "</script>\n";

        return $output;
    }

    /**
     * Validate a JavaScript identifier.
     *
     * @param string $identifier
     * @return void
     */
    private function assertJsIdentifier(string $identifier): void
    {
        if (!preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$]*$/D', $identifier)) {
            throw new \RuntimeException(
                "Invalid JavaScript identifier '{$identifier}'."
            );
        }
    }

    private function importMap(): string
    {
        $imports = $this->html->importMap();

        if ($imports === []) {
            return '';
        }

        $content = json_encode(
            ['imports' => $imports],
            JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT
                | JSON_THROW_ON_ERROR
        );

        $attributes = ['type' => 'importmap'];

        if ($this->html->nonce() !== null) {
            $attributes['nonce'] = $this->html->nonce();
        }

        return '<script'
            . $this->renderAttributes($attributes)
            . ">\n"
            . $content
            . "\n</script>\n";
    }
}
