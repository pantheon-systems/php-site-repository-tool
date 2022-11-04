<?php

namespace PhpSiteRepositoryTool\Utils;

/**
 * Process - a simpler version of Symfony Process
 *
 * Symfony Process doesn't work right for us in early versions of PHP.
 * Here's a simpler version that does only what we need: syncronous execution
 * with a bucket to store return values.
 */
class Process
{
    private $hasRun;

    private $command;

    private $cwd;

    private array $env;

    private $exitCode;

    private $output;

    private $errorOutput;

    public function __construct($command, $cwd = '', array $env = [], $input = null, $unused = 0)
    {
        $this->hasRun = false;
        $this->command = $command;
        $this->cwd = $cwd;
        $this->env = $env;
        $this->exitCode = 127;
        $this->output = '';
        $this->errorOutput = '';
    }

    public function run()
    {
        $command = implode(' ', array_map('escapeshellarg', $this->command));

        $descriptorspec = [
           0 => ["pipe", "r"],  // stdin is a pipe that the child will read from
           1 => ["pipe", "w"],  // stdout is a pipe that the child will write to
           2 => ["pipe", "w"]   // stderr is a pipe that the child will write to
        ];

        // Process envrionment like Symfony\Process does
        $env = [];
        $inheritedEnv = $this->getInheritedEnvironment();
        if (empty($inheritedEnv)) {
            print "!!!!!!!! Could not get inherited environment!\n";
        }
        foreach ($this->env + $inheritedEnv as $k => $v) {
            if (false !== $v && false === \in_array($k, ['argc', 'argv', 'ARGC', 'ARGV'], true)) {
                $env[] = $k.'='.$v;
            }
        }

        $process = proc_open($command, $descriptorspec, $pipes, $this->cwd, $env);

        if (is_resource($process)) {
            // $pipes now looks like this:
            // 0 => writeable handle connected to child stdin
            // 1 => readable handle connected to child stdout
            // 2 => readable handle connected to child stderr

            // @todo: Allow clients to provide stdin
            // fwrite($pipes[0], $input);
            fclose($pipes[0]);

            $this->output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $this->errorOutput = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            // It is important that you close any pipes before calling
            // proc_close in order to avoid a deadlock
            $this->exitCode = proc_close($process);
        }

        $this->hasRun = true;
    }

    public function getExitCode()
    {
        $this->mustHaveRun();
        return $this->exitCode;
    }

    public function getOutput()
    {
        $this->mustHaveRun();
        return $this->output;
    }

    public function getErrorOutput()
    {
        $this->mustHaveRun();
        return $this->errorOutput;
    }

    private function mustHaveRun()
    {
        if (!$this->hasRun) {
            throw new \RuntimeException("Did not call run() on Process object.");
        }
    }

    private function getInheritedEnvironment()
    {
        if (version_compare(PHP_VERSION, '7.1.0') >= 0) {
            return getenv();
        }
        // Ideally, variables_order="EGPCS", and $_ENV contains a complete
        // set of environment variables. If "E" is missing, though, we want
        // to ensure that we have at least 'HOME' and 'PATH'.
        return $_ENV + ['HOME' => getenv('HOME'), 'PATH' => getenv('PATH')];
    }
}
