<?php
namespace PhpDslAdmin\Install;

use Composer\IO\IOInterface;

class IOWrapper
{
    private $io;

    private $config;

    private $useDefaults = false;

    public function __construct(IOInterface $io, Config $config = null)
    {
        $this->io = $io;
        $this->config = $config;
    }

    public function askConfirmation($question, $default = null)
    {
        if ($this->useDefaults === true && $default !== null)
            return $default;

        $default === true
            ? $question .= ' [Y/n] '
            : ($default === false
                ? $question .= ' [y/N] '
                : $question .= ' [y/n] ');

        while (1) {
            $input = $this->io->ask($question . ': ');

            if (trim($input) === '' && $default !== null)
                return $default;
            else if (trim($input) !== '')
                break;
            $this->io->write($question . ' cannot be empty!');
        }
        return $input;
    }

    public function askRequired($question, $default = null, $hidden = false)
    {
        if ($this->useDefaults === true && $default !== null)
            return $default;

        while (1) {
            if (!$hidden)
                $input = $this->io->ask($question . ': ' . ($default !== null ? '(' . $default . ') ' : ' '));
            else
                $input = $this->io->askAndHideAnswer($question . ': ');
            if (trim($input) === '' && $default !== null)
                return $default;
            else if (trim($input) !== '')
                break;
            $this->io->write($question . ' cannot be empty!');
        }
        return $input;
    }

    public function write($message, $newline = true)
    {
        $this->io->write($message, $newline);
    }

    public function useDefaults($useDefaults = true)
    {
        $this->useDefaults = $useDefaults;
    }
}
