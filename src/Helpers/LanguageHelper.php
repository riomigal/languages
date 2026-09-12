<?php

namespace Riomigal\Languages\Helpers;

class LanguageHelper
{
    /**
     * Converts an array to dot notation keys
     *
     * @template TValue
     * @param array<array-key, TValue> $content
     * @return array<array-key, scalar|null|resource> Leaf values from recursive arrays and object properties.
     */
    public function array_convert_keys_to_dot_notation(array $content): array
    {
        $recursiveIterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($content));
        $result = [];
        foreach ($recursiveIterator as $value) {
            $keys = array();
            foreach (range(0, $recursiveIterator->getDepth()) as $depth) {
                $key = $recursiveIterator->getSubIterator($depth)->key();
                if (!is_string($key) && !is_int($key)) {
                    throw new \TypeError('Recursive array keys must be strings or integers.');
                }
                $keys[] = (string) $key;
            }
            if (!is_scalar($value) && $value !== null && !is_resource($value) && gettype($value) !== 'resource (closed)') {
                throw new \TypeError('Recursive array leaves must be scalar values, resources or null.');
            }
            $result[join('.', $keys)] = $value;
        }
        return $result;
    }

    /**
     * Gets all files of a folder recursively
     *
     * @param string $dir
     * @param list<string|false> $results
     * @return list<string|false>
     */
    function get_dir_files_recursive(string $dir, array &$results = []): array
    {
        $files = scandir($dir);

        foreach ($files as $key => $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path === false ? '' : $path)) {
                $results[] = $path;
            } else if ($value != "." && $value != "..") {
                $this->get_dir_files_recursive($path === false ? '' : $path, $results);
            }
        }

        return $results;
    }

    /**
     * Counts all the language keys of all files recursively in a directory
     *
     * @param string $path
     * @return int
     */
    function count_all_array_values_in_directory(string $path, bool $excludeVendor = true): int
    {
        $files = $this->get_dir_files_recursive($path);

        $total = 0;
        foreach ($files as $file) {
            // Path functions previously coerced a failed realpath() to an empty string.
            $file = $file === false ? '' : $file;
            if($excludeVendor) {
                if (str_starts_with($file, $path . '/vendor')) continue;
            }

            $type = pathinfo($file, PATHINFO_EXTENSION);
            if ($type == 'php') {
                $content = require($file);
            } elseif ($type == 'json') {
                $json = file_get_contents($file);
                $content = json_decode($json === false ? '' : $json, true);
            } else {
                continue;
            }
            if (!is_array($content)) {
                throw new \TypeError('Translation file content must be an array.');
            }
            $content = $this->array_convert_keys_to_dot_notation($content);
            $total += count($content);

        }
        return $total;
    }


}
