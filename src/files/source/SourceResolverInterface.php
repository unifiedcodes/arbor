<?php

namespace Arbor\files\source;


interface SourceResolverInterface
{
    public static function resolve(mixed $source): string;
}
