<?php

namespace App\Services;

class CommandService
{
    /**
     * Execute a command
     *
     * @param  string  $command  The command to execute
     * @return mixed The output from the command
     */
    public function execute(string $command)
    {
        return exec($command);
    }
}
