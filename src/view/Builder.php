<?php

namespace Arbor\view;

use Exception;
use InvalidArgumentException;
use Arbor\contracts\handlers\ControllerInterface;
use Arbor\fragment\Fragment;
use Arbor\attributes\ConfigValue;

/**
 * HTML Document Builder
 * 
 * A comprehensive builder class for constructing HTML documents with advanced component management.
 * Provides a fluent interface for building HTML documents by managing metadata, assets (CSS/JS),
 * body content, and reusable components with hierarchical builder instances using a root node pattern
 * for shared state management.
 * 
 * Features:
 * - Document metadata management (title, charset, language)
 * - CSS and JavaScript asset management (external and inline)
 * - Meta tags and link elements management
 * - Body content configuration with multiple content types
 * - Variable binding system for template data
 * - Automatic content escaping for security
 * - Hierarchical builder instances with shared state
 * - Template rendering integration
 * 
 * @package Arbor\view
 */
class Builder
{
    // =========================================================================
    // PROPERTIES
    // =========================================================================

    /**
     * Reference to the root builder instance for shared state management.
     * When set, this instance delegates shared operations to the root node.
     */
    protected ?Builder $rootNode = null;

    /**
     * Document title displayed in the browser tab.
     */
    protected string $title = '';

    /**
     * Character encoding for the HTML document (default: UTF-8).
     */
    protected string $charset = 'utf-8';

    /**
     * Language attribute for the HTML document (default: English).
     */
    protected string $lang = 'en';

    /**
     * Collection of link elements for the document head.
     * Each element contains attributes like 'rel', 'href', 'media', etc.
     * 
     * @var array<int, array<string, string>>
     */
    protected array $links = [];

    /**
     * Collection of meta elements for the document head.
     * Each element contains 'name' and 'content' attributes.
     * 
     * @var array<int, array<string, string>>
     */
    protected array $metas = [];

    /**
     * Collection of external script references with their attributes.
     * Each element contains 'src' and optional attributes like 'defer', 'async'.
     * 
     * @var array<int, array<string, string>>
     */
    protected array $scripts = [];

    /**
     * Collection of inline CSS styles to be embedded in the document.
     * Each element is a CSS code block as a string.
     * 
     * @var array<int, string>
     */
    protected array $inlineStyles = [];

    /**
     * Collection of inline JavaScript code blocks to be embedded in the document.
     * Each element is a JavaScript code block as a string.
     * 
     * @var array<int, string>
     */
    protected array $inlineScripts = [];

    /**
     * HTML attributes to be applied to the body element.
     * Key-value pairs where key is attribute name and value is attribute value.
     * 
     * @var array<string, string>
     */
    protected array $bodyAttributes = [];

    /**
     * Main body content configuration.
     * Can be either a configuration array with 'type' and 'content' keys,
     * or a direct HTML string for backward compatibility.
     * 
     * @var array<string, mixed>|string
     */
    protected array|string $body = [];

    /**
     * Additional HTML content chunks to append to the body.
     * Used for dynamically adding content after the main body is set.
     * 
     * @var array<int, string>
     */
    protected array $toAppendBody = [];

    /**
     * Variable bindings for template data.
     * Key-value pairs accessible in templates and for path building.
     * 
     * @var array<string, mixed>
     */
    protected array $bindings = [];

    /**
     * Data container for passing structured information to views.
     * Separate from bindings to maintain clean separation of concerns.
     * 
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Auto-escape flag for content security.
     * When true, content is automatically escaped to prevent XSS attacks.
     */
    protected bool $autoEscapeContent = true;

    /**
     * Base directory path for view templates.
     * Injected via dependency injection using ConfigValue attribute.
     */
    protected string $view_dir = '';

    /**
     * Fragment instance for handling template fragments and components.
     * Provides advanced templating capabilities and component rendering.
     */
    protected Fragment $fragment;

    // =========================================================================
    // CONSTRUCTOR
    // =========================================================================

    /**
     * Initialize the Builder with required dependencies.
     * 
     * @param string   $view_dir Base directory for view templates (injected from config)
     * @param Fragment $fragment Fragment instance for template handling
     */
    public function __construct(
        #[ConfigValue('app.views_dir')]
        string $view_dir,
        Fragment $fragment
    ) {
        $this->view_dir = $view_dir;
        $this->fragment = $fragment;
    }

    // =========================================================================
    // ROOT NODE MANAGEMENT
    // =========================================================================

    /**
     * Set the root builder instance for shared state management.
     * 
     * When working with hierarchical builders, the root node maintains shared state
     * such as assets, meta tags, and other document-level configurations.
     * 
     * @param Builder $root The root builder instance to use for shared state
     */
    public function setRoot(Builder $root): void
    {
        $this->rootNode = $root;
    }

    /**
     * Get the root builder instance.
     * 
     * Returns the configured root node if available, otherwise returns self.
     * This ensures that shared state operations always work on the correct instance.
     * 
     * @return Builder The root builder instance (self if no root is configured)
     */
    public function getRoot(): Builder
    {
        return $this->rootNode ?? $this;
    }

    // =========================================================================
    // VARIABLE BINDINGS MANAGEMENT
    // =========================================================================

    /**
     * Bind a variable value to a key for template use.
     * 
     * Bindings are accessible in templates and can be used for dynamic path building.
     * Values can be of any type and are stored in the current builder instance.
     * 
     * @param string $key   The variable key/name
     * @param mixed  $value The value to bind to the key
     */
    public function set(string $key, mixed $value): void
    {
        $this->bindings[$key] = $value;
    }

    /**
     * Retrieve a bound variable value by key.
     * 
     * @param string|null $key     The variable key to retrieve (null returns all bindings)
     * @param mixed       $default Default value if key doesn't exist
     * 
     * @return mixed The variable value, all bindings if key is null, or default value
     */
    public function get(string|null $key = null, mixed $default = null): mixed
    {
        if (empty($key)) {
            return $this->bindings;
        }

        return $this->bindings[$key] ?? $default;
    }

    /**
     * Replace the entire bindings array with a new set of bindings.
     * 
     * This completely overwrites existing bindings. Use with caution as it may
     * affect other parts of the application that depend on existing bindings.
     * 
     * @param array<string, mixed> $bindings New bindings to replace existing ones
     */
    public function replace(array $bindings): void
    {
        $this->bindings = $bindings;
    }

    // =========================================================================
    // DOCUMENT METADATA MANAGEMENT
    // =========================================================================

    /**
     * Set the document title.
     * 
     * The title appears in the browser tab and is used by search engines
     * and social media platforms when sharing the page.
     * 
     * @param string $title The document title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * Get the current document title.
     * 
     * @return string The document title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set the document character encoding.
     * 
     * Specifies the character encoding used in the HTML document.
     * UTF-8 is recommended for modern web applications.
     * 
     * @param string $charset The character encoding (e.g., 'utf-8', 'iso-8859-1')
     */
    public function setCharset(string $charset): void
    {
        $this->charset = $charset;
    }

    /**
     * Get the current document character encoding.
     * 
     * @return string The character encoding
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * Set the document language attribute.
     * 
     * Specifies the primary language of the document content.
     * Used by screen readers and translation tools.
     * 
     * @param string $lang The language code (e.g., 'en', 'fr', 'es', 'de')
     */
    public function setLang(string $lang): void
    {
        $this->lang = $lang;
    }

    /**
     * Get the current document language attribute.
     * 
     * @return string The language code
     */
    public function getLang(): string
    {
        return $this->lang;
    }

    // =========================================================================
    // META TAGS MANAGEMENT
    // =========================================================================

    /**
     * Add a meta tag to the document head.
     * 
     * Meta tags provide metadata about the HTML document, such as descriptions,
     * keywords, author information, and viewport settings.
     * 
     * @param string $name    The meta tag name attribute
     * @param string $content The meta tag content attribute
     */
    public function addMeta(string $name, string $content): void
    {
        $metas = $this->getRoot()->getMetas();
        $metas[] = ['name' => $name, 'content' => $content];
        $this->getRoot()->setMetas($metas);
    }

    /**
     * Get all configured meta tags.
     * 
     * @return array<int, array<string, string>> Array of meta tag configurations
     */
    public function getMetas(): array
    {
        return $this->metas;
    }

    /**
     * Set the meta tags array (internal use).
     * 
     * @param array<int, array<string, string>> $metas Array of meta tag configurations
     */
    protected function setMetas(array $metas): void
    {
        $this->metas = $metas;
    }

    // =========================================================================
    // LINK ELEMENTS MANAGEMENT
    // =========================================================================

    /**
     * Add a link element to the document head.
     * 
     * Link elements define relationships between the current document and external resources.
     * Common uses include stylesheets, favicons, and canonical URLs.
     * 
     * @param string                    $rel        The relationship attribute (e.g., 'stylesheet', 'icon', 'canonical')
     * @param string                    $href       The URL or path to the linked resource
     * @param array<string, string>     $attributes Additional attributes for the link element
     * 
     * @throws InvalidArgumentException When href is not a valid URL or path
     */
    public function addLink(string $rel, string $href, array $attributes = []): void
    {
        $this->validateAssetURI($href);

        $links = $this->getRoot()->getLinks();
        $links[] = array_merge(['rel' => $rel, 'href' => $href], $attributes);
        $this->getRoot()->setLinks($links);
    }

    /**
     * Get all configured link elements.
     * 
     * @return array<int, array<string, string>> Array of link element configurations
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * Set the links array (internal use).
     * 
     * @param array<int, array<string, string>> $links Array of link element configurations
     */
    protected function setLinks(array $links): void
    {
        $this->links = $links;
    }

    /**
     * Add a base link element to set the base URL for relative URLs.
     * 
     * The base element specifies the base URL to use for all relative URLs in the document.
     * This is useful when the document needs to reference resources from a different location.
     * 
     * @param string $href The base URL
     * 
     * @throws InvalidArgumentException When href is not a valid URL
     */
    public function addBaseLink(string $href): void
    {
        $this->validateAssetURI($href);
        $this->addLink('base', $href);
    }

    // =========================================================================
    // CSS STYLES MANAGEMENT
    // =========================================================================

    /**
     * Add a CSS stylesheet link to the document.
     * 
     * Links to an external CSS file that will be loaded and applied to the document.
     * Supports media queries for responsive design and conditional loading.
     * 
     * @param string $href     The URL or path to the stylesheet
     * @param string $media    The media query for the stylesheet (default: 'all')
     * @param string $fromPath Optional path prefix variable name from bindings
     * 
     * @throws InvalidArgumentException When href is not a valid URL or path
     * @throws Exception                When fromPath variable is not bound
     */
    public function addStyle(string $href, string $media = 'all', string $fromPath = ''): void
    {
        $href = $this->buildPath($href, $fromPath);
        $this->validateAssetURI($href);
        $this->addLink('stylesheet', $href, ['media' => $media]);
    }

    /**
     * Add inline CSS styles to be embedded in the document.
     * 
     * Inline styles are embedded directly in the HTML document within <style> tags.
     * Use sparingly as they can affect caching and performance.
     * 
     * @param string $style The CSS code to include inline
     */
    public function inlineStyle(string $style): void
    {
        $styles = $this->getRoot()->getInlineStyles();
        $styles[] = $style;
        $this->getRoot()->setInlineStyles($styles);
    }

    /**
     * Get all configured inline styles.
     * 
     * @return array<int, string> Array of inline CSS code blocks
     */
    public function getInlineStyles(): array
    {
        return $this->inlineStyles;
    }

    /**
     * Set the inline styles array (internal use).
     * 
     * @param array<int, string> $styles Array of inline CSS code blocks
     */
    protected function setInlineStyles(array $styles): void
    {
        $this->inlineStyles = $styles;
    }

    // =========================================================================
    // JAVASCRIPT MANAGEMENT
    // =========================================================================

    /**
     * Add an external JavaScript file to the document.
     * 
     * Links to an external JavaScript file that will be loaded and executed.
     * Supports attributes like 'defer', 'async', and 'type' for advanced loading behavior.
     * 
     * @param string                    $src        The URL or path to the JavaScript file
     * @param array<string, string>     $attributes Additional attributes for the script element
     * @param string                    $fromPath   Optional path prefix variable name from bindings
     * 
     * @throws InvalidArgumentException When src is not a valid URL or path
     * @throws Exception                When fromPath variable is not bound
     */
    public function addScript(string $src, array $attributes = [], string $fromPath = ''): void
    {
        $src = $this->buildPath($src, $fromPath);
        $this->validateAssetURI($src);

        $scripts = $this->getRoot()->getScripts();
        $scripts[] = array_merge(['src' => $src], $attributes);
        $this->getRoot()->setScripts($scripts);
    }

    /**
     * Get all configured external scripts.
     * 
     * @return array<int, array<string, string>> Array of script configurations
     */
    public function getScripts(): array
    {
        return $this->scripts;
    }

    /**
     * Set the scripts array (internal use).
     * 
     * @param array<int, array<string, string>> $scripts Array of script configurations
     */
    protected function setScripts(array $scripts): void
    {
        $this->scripts = $scripts;
    }

    /**
     * Add inline JavaScript code to be embedded in the document.
     * 
     * Inline scripts are embedded directly in the HTML document within <script> tags.
     * Use for small snippets of JavaScript that don't warrant separate files.
     * 
     * @param string $script The JavaScript code to include inline
     */
    public function inlineScript(string $script): void
    {
        $inlineScripts = $this->getRoot()->getInlineScripts();
        $inlineScripts[] = $script;
        $this->getRoot()->setInlineScripts($inlineScripts);
    }

    /**
     * Get all configured inline scripts.
     * 
     * @return array<int, string> Array of inline JavaScript code blocks
     */
    public function getInlineScripts(): array
    {
        return $this->inlineScripts;
    }

    /**
     * Set the inline scripts array (internal use).
     * 
     * @param array<int, string> $scripts Array of inline JavaScript code blocks
     */
    protected function setInlineScripts(array $scripts): void
    {
        $this->inlineScripts = $scripts;
    }

    // =========================================================================
    // BODY CONTENT MANAGEMENT
    // =========================================================================

    /**
     * Set the main body content with specified type and content.
     * 
     * Supports multiple content types:
     * - 'controller': Uses a controller class to generate content
     * - 'template': Renders a template file
     * - 'html': Direct HTML content
     * - 'string': Plain text content
     * 
     * @param ControllerInterface|string|array<string, mixed> $content The content data
     * @param string                                          $type    The content type identifier
     */
    public function setBody(ControllerInterface|string|array $content, string $type = 'html'): void
    {
        $this->body = ['type' => $type, 'content' => $content];
    }

    /**
     * Set body content to use a specific template.
     * 
     * Convenience method for setting body content to render a template file.
     * The template will be processed with current bindings and data.
     * 
     * @param string $templateName The name/path of the template to render
     */
    public function setTemplate(string $templateName): void
    {
        $this->setBody($templateName, 'template');
    }

    /**
     * Set body content to direct HTML.
     * 
     * Convenience method for setting body content to raw HTML.
     * The HTML will be used as-is without additional processing.
     * 
     * @param string $html The HTML content to use for the body
     */
    public function setHTMLBody(string $html): void
    {
        $this->setBody($html, 'html');
    }

    /**
     * Set body content to use a controller.
     * 
     * Convenience method for setting body content to be generated by a controller.
     * The controller will be instantiated and executed to generate the content.
     * 
     * @param string $controller The controller class name to use
     */
    public function useController(string $controller): void
    {
        $this->setBody($controller, 'controller');
    }

    /**
     * Set body content to plain text.
     * 
     * Convenience method for setting body content to plain text.
     * The text will be escaped if auto-escaping is enabled.
     * 
     * @param string $string The plain text content to use for the body
     */
    public function setStringBody(string $string): void
    {
        $this->setBody($string, 'string');
    }

    /**
     * Get the current body content configuration.
     * 
     * @return array<string, mixed>|string The body configuration array or direct HTML content
     */
    public function getBody(): array|string
    {
        return $this->body;
    }

    /**
     * Append additional HTML content to the body.
     * 
     * Allows adding content to the body after the main content has been set.
     * Useful for dynamically adding scripts, analytics, or other content.
     * 
     * @param string $html The HTML content to append to the body
     */
    public function appendBodyContent(string $html): void
    {
        $chunks = $this->getRoot()->getToAppendBody();
        $chunks[] = $html;
        $this->getRoot()->setToAppendBody($chunks);
    }

    /**
     * Get all content chunks to be appended to the body.
     * 
     * @return array<int, string> Array of HTML content chunks
     */
    public function getToAppendBody(): array
    {
        return $this->toAppendBody;
    }

    /**
     * Set the content to be appended to the body (internal use).
     * 
     * @param array<int, string> $chunks Array of HTML content chunks
     */
    protected function setToAppendBody(array $chunks): void
    {
        $this->toAppendBody = $chunks;
    }

    // =========================================================================
    // BODY ATTRIBUTES MANAGEMENT
    // =========================================================================

    /**
     * Add an attribute to the body element.
     * 
     * Body attributes are applied to the <body> tag and can include classes,
     * IDs, data attributes, and other HTML attributes.
     * 
     * @param string $key   The attribute name
     * @param string $value The attribute value
     */
    public function addBodyAttribute(string $key, string $value): void
    {
        $attributes = $this->getRoot()->getBodyAttributes();
        $attributes[$key] = $value;
        $this->getRoot()->setBodyAttributes($attributes);
    }

    /**
     * Get all body element attributes.
     * 
     * @return array<string, string> Array of attribute name-value pairs
     */
    public function getBodyAttributes(): array
    {
        return $this->bodyAttributes;
    }

    /**
     * Set the body element attributes (internal use).
     * 
     * @param array<string, string> $attributes Array of attribute name-value pairs
     */
    protected function setBodyAttributes(array $attributes): void
    {
        $this->bodyAttributes = $attributes;
    }

    // =========================================================================
    // CONTENT SECURITY CONFIGURATION
    // =========================================================================

    /**
     * Configure automatic content escaping for security.
     * 
     * When enabled, content is automatically escaped to prevent XSS attacks.
     * This setting affects how templates and dynamic content are processed.
     * 
     * @param bool $enabled True to enable auto-escaping, false to disable
     */
    public function setAutoEscapeContent(bool $enabled): void
    {
        $this->getRoot()->autoEscapeContent = $enabled;
    }

    /**
     * Get the current auto-escape content setting.
     * 
     * @return bool True if auto-escaping is enabled, false otherwise
     */
    public function getAutoEscapeContent(): bool
    {
        return $this->getRoot()->autoEscapeContent;
    }

    // =========================================================================
    // CONTEXT AND STATE MANAGEMENT
    // =========================================================================

    /**
     * Get the current builder context for rendering.
     * 
     * Returns a structured array containing all the information needed
     * for rendering the document, including metadata, bindings, and settings.
     * 
     * @return array<string, mixed> The complete builder context
     */
    public function getContext(): array
    {
        return [
            'title'          => $this->getTitle(),
            'bodyAttributes' => $this->getBodyAttributes(),
            'bindings'       => $this->bindings,
            'autoEscape'     => $this->getAutoEscapeContent()
        ];
    }

    // =========================================================================
    // UTILITY METHODS
    // =========================================================================

    /**
     * Validate that a given string is a valid URL or asset path.
     * 
     * Ensures that asset references (CSS, JS, images) are properly formatted
     * to prevent broken links and security issues.
     * 
     * @param string $href The URL or path to validate
     * 
     * @throws InvalidArgumentException When href is not a valid URL or path
     */
    protected function validateAssetURI(string $href): void
    {
        if (!filter_var($href, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid asset path or URL: $href");
        }
    }

    /**
     * Build a complete path by combining base path with additional segment.
     * 
     * Used for constructing asset URLs by combining a base path (from bindings)
     * with a relative path segment. Enables flexible asset management.
     * 
     * @param string      $toAppend The path segment to append
     * @param string|null $pathVar  The base path variable name from bindings
     * 
     * @return string The complete combined path
     * 
     * @throws Exception When the path variable is not bound or is empty
     */
    protected function buildPath(string $toAppend, ?string $pathVar = null): string
    {
        if (empty($pathVar)) {
            return $toAppend;
        }

        $basePath = $this->getRoot()->get($pathVar);

        if (empty($basePath)) {
            throw new Exception(
                "Failed to build path using '{$pathVar}' - ensure the path variable is bound before use."
            );
        }

        return $basePath . $toAppend;
    }

    // =========================================================================
    // RENDERING METHODS
    // =========================================================================

    /**
     * Render the complete HTML document.
     * 
     * Generates the full HTML document including DOCTYPE, head section with all
     * metadata and assets, and the body with all configured content.
     * 
     * @return string The complete HTML document as a string
     */
    public function toHtml(): string
    {
        return (new Renderer($this, $this->view_dir, $this->fragment))->toHTML($this);
    }

    /**
     * Render only the body content as partial HTML.
     * 
     * Generates only the body content without the full HTML document structure.
     * Useful for AJAX responses, partial updates, or content fragments.
     * 
     * @return string The body content as HTML string
     */
    public function toPartialHtml(): string
    {
        return (new Renderer($this, $this->view_dir, $this->fragment))->renderBody();
    }
}
