<?php

namespace Arbor\contracts\file;


interface FileProcessorInterface
{
    public function process(array $meta = []);
}
