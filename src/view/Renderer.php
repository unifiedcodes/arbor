<?php

namespace Arbor\view;

use Exception;
use Arbor\view\Builder;
use Arbor\http\Response;
use InvalidArgumentException;
use Arbor\config\ConfigValue;
use Arbor\facades\Container;

class Renderer
{
    protected string $views_dir;
    protected Builder $builder;
    protected array $deferredComponents = [];


    public function __construct(
        Builder $builder,
        #[ConfigValue('app.views_dir')] string $views_dir,
    ) {
        $this->views_dir = $views_dir;
        $this->builder = $builder;
    }


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


    public function useComponent(string $component): string
    {
        // Generate a unique mount identifier
        $mount_id = bin2hex(random_bytes(8));

        // Queue component for deferred execution
        $this->addDeferredComponent($mount_id, $component);

        // Output mount point placeholder
        $mount = "<!--componentmount-{$mount_id}-->";
        return $mount;
    }


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


    public function addDeferredComponent(string $mount_id, string $component): void
    {
        $this->deferredComponents[$mount_id] = $component;
    }


    public function renderDeferredComponents(string $body): string
    {
        while (!empty($this->deferredComponents)) {

            // grab & clear queue so nested calls create a new queue
            $queue = $this->deferredComponents;
            $this->deferredComponents = [];

            $replacements = [];

            foreach ($queue as $mount_id => $component) {
                $mount = "<!--componentmount-{$mount_id}-->";
                $replacements[$mount] = $this->renderComponent($component);
            }

            $body = strtr($body, $replacements);
        }

        return $body;
    }


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


    protected function renderController(string $controller): string
    {
        // Execute controller through fragment system
        $controllerResponse = $this->controllerDispatcher(
            $controller,
            ['parentBuilder' => $this->builder]
        );

        // Validate content type
        $contentType = $controllerResponse->getHeaderLine('Content-Type');

        if (!str_starts_with(strtolower($contentType), 'text/html')) {
            throw new InvalidArgumentException('Component Controller "' . $controller . '" must return an HTML response, instead found: "' . $contentType . '"');
        }

        $stream = $controllerResponse->getBody();
        return $stream ? $stream->getContents() : '';
    }


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


    protected function renderHtmlHead(): string
    {
        $html = "<head>\n";
        $html .= "<meta charset=\"" . htmlspecialchars($this->builder->getCharset()) . "\">\n";

        if ($this->builder->getTitle() !== '') {
            $html .= "<title>" . htmlspecialchars($this->builder->getTitle()) . "</title>\n";
        }

        foreach ($this->builder->getMetas() as $meta) {
            if (isset($meta['name'])) {
                $html .= '<meta name="' . htmlspecialchars($meta['name']) . '" content="' . htmlspecialchars($meta['content']) . '">' . "\n";
            } elseif (isset($meta['property'])) {
                $html .= '<meta property="' . htmlspecialchars($meta['property']) . '" content="' . htmlspecialchars($meta['content']) . '">' . "\n";
            }
        }

        $html .= $this->buildLinks();

        foreach ($this->builder->getInlineStyles() as $style) {
            $html .= "<style>" . $style . "</style> \n";
        }

        $html .= "</head>\n";
        return $html;
    }


    protected function buildLinks(): string
    {
        $html = '';

        foreach ($this->builder->getLinks() as $link) {
            $attrs = $this->buildAttributes($link ?? []);
            $html .= "<link{$attrs}>\n";
        }

        return $html;
    }


    protected function buildScripts(): string
    {
        $html = '';

        // Inline scripts
        foreach ($this->builder->getInlineScripts() as $script) {
            $html .= "<script type=\"text/javascript\">\n{$script}\n</script>\n";
        }

        // External scripts
        foreach ($this->builder->getScripts() as $script) {
            $attrs = $this->buildAttributes($script ?? []);
            $html .= "<script{$attrs}></script>\n";
        }

        return $html;
    }


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


    protected function controllerDispatcher($controller, $parameters): Response
    {
        return Container::call($controller, $parameters);
    }
}
