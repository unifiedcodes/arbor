<?php

namespace Arbor\file;


interface FileProcessorInterface
{
    public function process(array $meta = []);
}
