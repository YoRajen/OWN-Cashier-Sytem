<?php

declare(strict_types=1);

namespace App\Services;


/**
 * Class LoadEnv,
 * Class responsible for loading environment variables from a .env file.
 * @package App\Services
 */
class LoadEnv
{

    /**  @var string $filePath The path to the .env folder. */
    private string $filePath;

    /** @var string[] $absolutePath An array of absolute paths to the .env files. */
    private array $absolutePath;

    /** @var string[] $envVariables An associative array of environment variables loaded from the .env files. */
    private array $envVariables;

    /**
     * Load environment variables from a specified .env folder.
     * @param string $path The path to the .env folder.
     * @throws \InvalidArgumentException If the specified folder path does not exist.
     * @throws \RuntimeException If there are issues reading the .env files.
     * @return LoadEnv An instance of LoadEnv after loading the environment variables.
     */
    public static function fromFile(string $path): LoadEnv
    {
        // Check Folder Existence
        if (!is_dir($path)) {
            $envFiles = glob(rtrim($path, '/\\') . '/*.env') ?: [];
        } elseif (is_file($path)){
            $envFiles = [$path];
        }
        else {
            throw new \InvalidArgumentException("The specified folder path does not exist: $path");
        }

        // Loop through the folder to find .env files
        
        $envVariables = [];
        $absolutePath = [];

        foreach ($envFiles as $file) {
            $absolutePath[] = realpath($file);
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) {
                throw new \RuntimeException("Error reading env file: $file");
            }
            
            foreach ($lines as $line) {
                if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                    continue; // Skip comments
                }
                [$name, $value] = explode('=', $line, 2);
                $envVariables[trim($name)] = trim($value);
            }
        }

        $instance = new self();
        $instance->filePath = $path;
        $instance->absolutePath = $absolutePath;
        $instance->envVariables = $envVariables;
        return $instance;
    }


    /**
     * Load the environment variables into the $_ENV superglobal.
     * @return void
     */
    public function load(): void
    {
        foreach ($this->envVariables as $name => $value) {
            $_ENV[$name] = $value;
        }
    }

    /**
     * Get the required environment variables and throw an exception if any are missing.
     * @param string[] $requiredVariables An array of required environment variable names.
     * @throws \RuntimeException If any required environment variable is missing.
     * @return void
     */
    public function getRequiredOnly(string ...$requiredVariables): void
    {
        foreach ($requiredVariables as $variable) {
            if (!array_key_exists($variable, $this->envVariables)) {
                throw new \RuntimeException("Required environment variable '$variable' is missing.");
            }
            $_ENV[$variable] = $this->envVariables[$variable];
        }
    } 
}