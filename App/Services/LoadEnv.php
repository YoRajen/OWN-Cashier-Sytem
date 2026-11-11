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
     * @param string $folderPath The path to the .env folder.
     * @throws \InvalidArgumentException If the specified folder path does not exist.
     * @throws \RuntimeException If there are issues reading the .env files.
     * @return LoadEnv An instance of LoadEnv after loading the environment variables.
     */
    public static function fromFile(string $folderPath): LoadEnv
    {
        // Check Folder Existence
        if (!is_dir($folderPath)) {
            throw new \InvalidArgumentException("The specified folder path does not exist: $folderPath");
        }

        // Loop through the folder to find .env files
        $envFiles = glob($folderPath . '/*.env');
        $envVariables = [];
        $absolutePath = [];

        foreach ($envFiles as $file) {
            $absolutePath[] = realpath($file);
            try {
                $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            } catch (\Exception $e) {
                throw new \RuntimeException("Error reading .env file: " . $e->getMessage());
            }
            
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue; // Skip comments
                }
                [$name, $value] = explode('=', $line, 2);
                $envVariables[trim($name)] = trim($value);
            }
        }

        $instance = new self();
        $instance->filePath = $folderPath;
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