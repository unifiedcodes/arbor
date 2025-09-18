<?php

namespace Arbor\view;

use Exception;
use Arbor\view\Builder;
use Arbor\http\Response;
use Arbor\fragment\Fragment;
use InvalidArgumentException;
use Arbor\attributes\ConfigValue;

/**
 * HTML Renderer Class
 * 
 * Responsible for rendering HTML documents from templates, components, and controllers.
 * Supports deferred component rendering, HTML head generation, and full document assembly.
 * 
 * @package Arbor\view
 * 
 */
class Renderer
{
    /**
     * Fragment system instance for handling controller execution
     */
    protected Fragment $fragment;

    /**
     * Directory path where view templates are stored
     */
    protected string $views_dir;

    /**
     * Builder instance containing page configuration and content
     */
    protected Builder $builder;

    /**
     * Array of components to be rendered after main body processing
     * 
     * @var array<string, string> Mount ID => Component mapping
     */
    protected array $deferredComponents = [];

    /**
     * Initialize the renderer with required dependencies
     * 
     * @param Builder $builder The page builder instance
     * @param string $views_dir Directory path for view templates (injected from config)
     * @param Fragment $fragment Fragment system for controller handling
     */
    public function __construct(
        Builder $builder,
        #[ConfigValue('app.views_dir')] string $views_dir,
        Fragment $fragment
    ) {
        $this->fragment = $fragment;
        $this->views_dir = $views_dir;
        $this->builder = $builder;
    }

    // ==========================================
    // PUBLIC API METHODS
    // ==========================================

    /**
     * Generate complete HTML document
     * 
     * Orchestrates the entire rendering process:
     * 1. Renders the main body content
     * 2. Generates HTML head section
     * 3. Processes deferred components
     * 4. Assembles final HTML document
     * 
     * @return string Complete HTML document string
     */
    public function toHTML(): string
    {
        // Execute body first to capture any deferred components
        $body = $this->renderBody();

        $head = $this->renderHtmlHead();
        $extraBody = $this->renderBodyChunks();

        // Process deferred components by replacing mount points
        $body = $this->renderDeferredComponents($body);

        $scripts = $this->buildScripts();
        $attrs = $this->buildAttributes($this->builder->getBodyAttributes() ?? []);
        $lang = htmlspecialchars($this->builder->getLang());

        $html = "<!DOCTYPE html>\n<html lang=\"{$lang}\">\n";
        $html .= $head . "\n";
        $html .= "<body{$attrs}>" . "\n";
        $html .= $body . "\n";
        $html .= $extraBody . "\n";
        $html .= $scripts . "\n";
        $html .= "</body>";
        $html .= "\n</html>";

        return $html;
    }

    /**
     * Register a component for deferred rendering
     * 
     * Creates a mount point placeholder and queues the component for later rendering.
     * This allows components to be processed after the main template execution.
     * 
     * @param string $component Component identifier (template path or controller class)
     * @return void
     */
    public function useComponent(string $component): void
    {
        // Generate a unique mount identifier
        $mount_id = bin2hex(random_bytes(8));

        // Queue component for deferred execution
        $this->addDeferredComponent($mount_id, $component);

        // Output mount point placeholder
        $mount = "<!--componentmount-{$mount_id}-->";
        echo $mount;
    }

    // ==========================================
    // BODY RENDERING METHODS
    // ==========================================

    /**
     * Render the main body content
     * 
     * Processes the primary body content based on its type:
     * - 'template': Renders a template file
     * - 'html': Returns raw HTML content
     * 
     * @return string Rendered body HTML
     */
    public function renderBody(): string
    {
        $body = $this->builder->getBody();

        if (empty($body)) {
            return '';
        }

        $renderedBody = '';

        if ($body['type'] === 'template') {
            $renderedBody = $this->renderTemplate($body['content']);
        }

        if ($body['type'] === 'html') {
            $renderedBody = $body['content'];
        }

        return $renderedBody;
    }

    /**
     * Render additional body chunks
     * 
     * Processes content that should be appended to the body,
     * with optional auto-escaping based on builder configuration.
     * 
     * @return string Rendered body chunks HTML
     */
    protected function renderBodyChunks(): string
    {
        $html = '';
        foreach ($this->builder->getRoot()->getToAppendBody() as $chunk) {
            $html .= $this->builder->getAutoEscapeContent()
                ? htmlspecialchars($chunk)
                : $chunk;
            $html .= "\n";
        }
        return $html;
    }

    // ==========================================
    // COMPONENT RENDERING METHODS
    // ==========================================

    /**
     * Add a component to the deferred rendering queue
     * 
     * @param string $mount_id Unique identifier for the mount point
     * @param string $component Component identifier to be rendered
     * @return void
     */
    public function addDeferredComponent(string $mount_id, string $component): void
    {
        $this->deferredComponents[$mount_id] = $component;
    }

    /**
     * Process all deferred components
     * 
     * Renders each queued component and replaces its mount point
     * placeholder in the body content.
     * 
     * @param string $body The body HTML containing mount points
     * @return string Body HTML with components rendered in place
     */
    public function renderDeferredComponents(string $body): string
    {
        $rendered_components = [];

        foreach ($this->deferredComponents as $mount_id => $component) {
            $mount = "<!--componentmount-{$mount_id}-->";
            $rendered_components[$mount] = $this->renderComponent($component);
        }

        // Replace all mount points with rendered components
        return strtr($body, $rendered_components);
    }

    /**
     * Render a single component
     * 
     * Determines component type and renders accordingly:
     * - Controller class: Executes controller and returns HTML response
     * - Template file: Renders template file
     * 
     * @param string $component Component identifier
     * @return string Rendered component HTML
     * @throws Exception When component type is invalid or not found
     */
    public function renderComponent(string $component): string
    {
        // Check if component is a controller class
        if (class_exists($component)) {
            return $this->renderController($component);
        }

        // Treat as template file
        $component = rtrim($component, '.php') . '.php';
        $component_file = $this->views_dir . $component;

        if (file_exists($component_file)) {
            return $this->renderTemplate($component);
        }

        throw new Exception("Invalid Component Called");
    }

    /**
     * Execute and render a controller component
     * 
     * Uses the fragment system to execute the controller and validates
     * that it returns a proper HTML response.
     * 
     * @param string $controller Fully qualified controller class name
     * @return string Rendered controller HTML output
     * @throws InvalidArgumentException When controller doesn't return valid HTML response
     */
    protected function renderController(string $controller): string
    {
        // Execute controller through fragment system
        $controllerResponse = $this->fragment->controller($controller, ['parentBuilder' => $this->builder]);

        // Validate response object
        if (!$controllerResponse instanceof Response) {
            throw new InvalidArgumentException('Controller did not return a valid Response object.');
        }

        // Validate content type
        $contentType = $controllerResponse->getHeaderLine('Content-Type');

        if (!str_starts_with(strtolower($contentType), 'text/html')) {
            throw new InvalidArgumentException('Component Controller "' . $controller . '" must return an HTML response, instead found: "' . $contentType . '"');
        }

        return $controllerResponse->getBody();
    }

    // ==========================================
    // TEMPLATE RENDERING METHODS
    // ==========================================

    /**
     * Render a template file
     * 
     * Includes the template file with access to builder context data,
     * builder instance, and view renderer instance.
     * 
     * @param string $templateName Template file name/path
     * @return string Rendered template HTML
     */
    protected function renderTemplate(string $templateName): string
    {
        // ensure .php extension
        if (!str_ends_with($templateName, '.php')) {
            $templateName .= '.php';
        }

        $template_file_path = normalizeFilePath($this->views_dir . $templateName);

        // Make variables available to template
        $builder = $this->builder;
        $data = $this->builder->getContext();
        $renderer = $this;

        // Capture template output
        ob_start();
        include $template_file_path;
        $output = ob_get_clean();

        return $output;
    }

    // ==========================================
    // HTML HEAD GENERATION METHODS
    // ==========================================

    /**
     * Generate HTML head section
     * 
     * Assembles the complete head section including:
     * - Meta charset
     * - Page title
     * - Meta tags
     * - Link tags (stylesheets, etc.)
     * - Inline styles
     * 
     * @return string Complete HTML head section
     */
    protected function renderHtmlHead(): string
    {
        $html = "<head>\n";
        $html .= "<meta charset=\"" . htmlspecialchars($this->builder->getCharset()) . "\">\n";

        if ($this->builder->getTitle() !== '') {
            $html .= "<title>" . htmlspecialchars($this->builder->getTitle()) . "</title>\n";
        }

        foreach ($this->builder->getMetas() as $meta) {
            $html .= "<meta name=\"" . htmlspecialchars($meta['name'])
                . "\" content=\"" . htmlspecialchars($meta['content']) . "\">\n";
        }

        $html .= $this->buildLinks();

        foreach ($this->builder->getInlineStyles() as $style) {
            $html .= "<style>" . $style . "</style> \n";
        }

        $html .= "</head>\n";
        return $html;
    }

    /**
     * Generate link tags for external resources
     * 
     * @return string HTML link tags
     */
    protected function buildLinks(): string
    {
        $html = '';

        foreach ($this->builder->getLinks() as $link) {
            $attrs = $this->buildAttributes($link ?? []);
            $html .= "<link{$attrs}>\n";
        }

        return $html;
    }

    // ==========================================
    // SCRIPT GENERATION METHODS
    // ==========================================

    /**
     * Generate script tags for JavaScript
     * 
     * Builds both external script references and inline script blocks.
     * 
     * @return string HTML script tags
     */
    protected function buildScripts(): string
    {
        $html = '';

        // External scripts
        foreach ($this->builder->getScripts() as $script) {
            $attrs = $this->buildAttributes($script ?? []);
            $html .= "<script{$attrs}></script>\n";
        }

        // Inline scripts
        foreach ($this->builder->getInlineScripts() as $script) {
            $html .= "<script type=\"text/javascript\">\n{$script}\n</script>\n";
        }

        return $html;
    }

    // ==========================================
    // UTILITY METHODS
    // ==========================================

    /**
     * Build HTML attributes string from array
     * 
     * Converts an associative array of attributes into a properly
     * escaped HTML attributes string.
     * 
     * @param array<string, string> $attributes Key-value pairs of attributes
     * @return string Formatted HTML attributes string (with leading space)
     */
    protected function buildAttributes(array $attributes): string
    {
        $html = '';

        foreach ($attributes as $key => $value) {
            $key = htmlspecialchars($key, ENT_QUOTES, $this->builder->getCharset());
            $value = htmlspecialchars($value, ENT_QUOTES, $this->builder->getCharset());
            $html .= " {$key}=\"{$value}\"";
        }

        return $html;
    }
}
