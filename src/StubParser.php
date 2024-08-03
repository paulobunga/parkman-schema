<?php

namespace Paulobunga\ParkmanSchema;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class StubParser
{
    /**
     * The path to the stubs directory.
     *
     * @var string
     */
    protected $stubPath;

    /**
     * Create a new StubParser instance.
     *
     * @param string $stubPath
     */
    public function __construct($stubPath)
    {
        $this->stubPath = $stubPath;
    }

    /**
     * Parse a stub file and replace placeholders.
     *
     * @param string $stubName
     * @param array $replacements
     * @return string
     */
    public function parse($stubName, $replacements = [])
    {
        $stubContent = $this->getStubContent($stubName);

        return $this->replacePlaceholders($stubContent, $replacements);
    }

    /**
     * Get the content of a stub file.
     *
     * @param string $stubName
     * @return string
     * @throws \Exception
     */
    protected function getStubContent($stubName)
    {
        $stubFile = $this->stubPath . "/{$stubName}.stub";

        if (!File::exists($stubFile)) {
            throw new \Exception("Stub file not found: {$stubFile}");
        }

        return File::get($stubFile);
    }

    /**
     * Replace placeholders in the stub content.
     *
     * @param string $stubContent
     * @param array $replacements
     * @return string
     */
    protected function replacePlaceholders($stubContent, $replacements)
    {
        foreach ($replacements as $key => $value) {
            $placeholder = "{{ {$key} }}";

            // If the value is an array, implode it
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            // If the placeholder is wrapped in quotes, ensure the replacement is properly quoted
            if (Str::contains($stubContent, "'{$placeholder}'")) {
                $value = addslashes($value);
            }

            $stubContent = str_replace($placeholder, $value, $stubContent);
        }

        return $stubContent;
    }

    /**
     * Set a new stub path.
     *
     * @param string $stubPath
     * @return $this
     */
    public function setStubPath($stubPath)
    {
        $this->stubPath = $stubPath;

        return $this;
    }

    /**
     * Get the current stub path.
     *
     * @return string
     */
    public function getStubPath()
    {
        return $this->stubPath;
    }
}