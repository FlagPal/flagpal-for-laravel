<?php

namespace FlagPal\FlagPal\Exceptions;

use RuntimeException;

class InvalidConfigurationException extends RuntimeException
{
    public static function unknownProject(string $project, array $knownProjects): self
    {
        $known = empty($knownProjects) ? '(none configured)' : implode(', ', $knownProjects);

        return new self(
            "FlagPal project \"{$project}\" is not defined in config('flagpal.projects'). ".
            "Known projects: {$known}. ".
            'Check FLAGPAL_PROJECT and the keys of the `projects` array in config/flagpal.php.'
        );
    }
}
