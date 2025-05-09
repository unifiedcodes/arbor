<?php


namespace Arbor\html;


class HTMLPage
{
    private $title;
    private $head;
    private $body;
    private $scripts;
    private $bodyAttributes;


    public function __construct($title = '')
    {
        $this->title = $title;
    }


    public function setTitle($title)
    {
        $this->title = $title;
    }


    public function setFavicon($faviconUrl)
    {
        $this->head .= "<link rel='icon' type='image/x-icon' href='$faviconUrl' />\n";

        return $this;
    }


    public function addMeta($name, $content)
    {
        $this->head .= "<meta name='$name' content='$content' />\n";

        return $this;
    }


    public function addStyleSheet($href, $attributes = [])
    {
        $linkTag = "<link rel='stylesheet' type='text/css' href='$href'";

        foreach ($attributes as $attribute => $value) {
            $linkTag .= " $attribute='$value'";
        }

        $linkTag .= " />\n";

        $this->head .= $linkTag;

        return $this;
    }



    public function addScript($src, $attributes = [], $position = 'body')
    {
        $scriptTag = "<script src='$src'";

        foreach ($attributes as $attribute => $value) {
            $scriptTag .= " $attribute='$value'";
        }

        $scriptTag .= "></script>\n";

        if ($position == 'body') {
            $this->scripts .= $scriptTag;
        }
        if ($position == 'head') {
            $this->head .= $scriptTag;
        }

        return $this;
    }



    public function concat($content, $part = null)
    {
        switch ($part) {
            case 'head':
                $this->head .= $content;
                break;

            case 'scripts':
                $this->scripts .= $content;
                break;

            default:
                $this->body .= $content;
                break;
        }

        return $this;
    }



    public function body_template($path)
    {
        if (!file_exists($path)) {
            throw new \Exception("Template file not found: $path");
        }

        $templateContents = file_get_contents($path);
        $this->body .= $templateContents;

        return $this;
    }



    public function parse_content($phpFilePath, $payload = [])
    {
        // Check if the file exists
        if (file_exists($phpFilePath)) {
            // Start output buffering
            ob_start();

            // Include the PHP file, which will execute its code and generate output
            include($phpFilePath);

            // Get the output from the included file and append it to the content property
            $output = ob_get_clean();
            $this->body .= $output;
        } else {
            // Handle the case when the file doesn't exist (e.g., throw an error)
            throw new \Exception("File not found: $phpFilePath");
        }
    }


    /**
     * Add content from a view class.
     *
     * @param string $viewClass - Fully qualified view class name (e.g., 'app\views\Sidebar')
     * @param array $params - Optional parameters to pass to the view class
     */
    public function view($viewClass, $params = [])
    {
        // Ensure the class exists
        if (!class_exists($viewClass)) {
            throw new \Exception("View class $viewClass not found");
        }

        // Instantiate the view class (with optional parameters)
        $view = new $viewClass(...$params);

        // Call the render() method of the view class
        $viewContent = $view->render();

        // Append the content to the body of the page
        $this->concat($viewContent);

        return $this; // Allow method chaining
    }



    public function setBodyAttributes($attributes)
    {
        if (!is_array($attributes)) {
            throw new \Exception('Attributes must be provided as an array.');
        }

        $attributeString = implode(' ', array_map(function ($key, $value) {
            return "$key='$value'";
        }, array_keys($attributes), $attributes));

        $this->bodyAttributes = $attributeString;

        return $this;
    }



    public function importConfig($configObj = [])
    {
        $configs = "";

        foreach ($configObj as $key => $config) {
            $configs .= "const $key = '$config'; \n";
        }


        $script = "\n<script>$configs</script>\n";

        $this->concat($script, 'scripts');
    }



    public function document()
    {
        $html = "<!DOCTYPE html>\n";
        $html .= "<html>\n";
        $html .= "<head>\n";

        $html .= "<meta charset='UTF-8'>\n";

        $html .= "<title>{$this->title}</title>\n";
        $html .= "{$this->head}";

        $html .= "</head>\n";

        $html .= "<body {$this->bodyAttributes} >\n";
        $html .= "{$this->body}\n";
        $html .= "{$this->scripts}";
        $html .= "</body>\n";

        $html .= "</html>";

        return $html;
    }
}
