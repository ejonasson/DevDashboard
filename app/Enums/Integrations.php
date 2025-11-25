<?php

namespace App\Enums;

enum Integrations: string
{
    case GitHub = 'github';
    case Jira = 'jira';
    case Aws = 'aws';
}
