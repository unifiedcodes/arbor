<?php


namespace Arbor\contracts\handlers;


use Arbor\http\Response;
use Arbor\view\ViewFactory;
use Arbor\http\ResponseFactory;
use Arbor\http\context\RequestContext;
use Arbor\contracts\handlers\ControllerInterface;


abstract class Controller implements ControllerInterface
{

    protected ViewFactory $viewFactory;
    protected ResponseFactory $response;


    public function __construct(
        ViewFactory $viewFactory,
        ResponseFactory $response
    ) {
        $this->viewFactory = $viewFactory;
        $this->response = $response;
    }


    abstract public function process(RequestContext $input): Response;
}
