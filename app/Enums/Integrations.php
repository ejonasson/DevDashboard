<?php

namespace App\Enums;

enum Integrations: string
{
    case GitHub = 'github';
    case Jira = 'jira';
    case Aws = 'aws';

    public function label(): string
    {
        return match ($this) {
            self::GitHub => 'GitHub',
            self::Jira => 'Jira',
            self::Aws => 'Amazon Web Services',
        };
    }

    public static function versionControl(): array
    {
        return [
            self::GitHub,
        ];
    }

    public static function planning(): array
    {
        return [
            self::Jira,
        ];
    }

    public static function deployment(): array
    {
        return [
            self::Aws,
        ];
    }
}
