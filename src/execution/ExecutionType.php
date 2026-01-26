<?php

namespace Arbor\execution;


enum ExecutionType: string
{
    case HTTP = 'http';
    case CLI  = 'cli';
    case JOB  = 'job';
}
