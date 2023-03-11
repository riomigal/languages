<?php

namespace Riomigal\Languages\Helpers;

class LanguageHelper
{
    /**
     * Converts an array to dot notation keys
     *
     * @param array $content
     * @return array
     */
    public function array_convert_keys_to_dot_notation(array $content): array
    {
        $recursiveIterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($content));
        $result = [];
        foreach ($recursiveIterator as $value) {
            $keys = array();
            foreach (range(0, $recursiveIterator->getDepth()) as $depth) {
                $keys[] = $recursiveIterator->getSubIterator($depth)->key();
            }
            $result[join('.', $keys)] = $value;
        }
        return $result;
    }

    /**
     * Gets all files of a folder recursively
     *
     * @param string $dir
     * @param array $results
     * @return array
     */
    function get_dir_files_recursive(string $dir, array &$results = []): array
    {
        $files = scandir($dir);

        foreach ($files as $key => $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path)) {
                $results[] = $path;
            } else if ($value != "." && $value != "..") {
                $this->get_dir_files_recursive($path, $results);
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
    function count_all_array_values_in_directory(string $path): int
    {
        $files = $this->get_dir_files_recursive($path);

        $total = 0;
        foreach ($files as $file) {

            $type = pathinfo($file, PATHINFO_EXTENSION);
            if ($type == 'php') {
                $content = require($file);
            } elseif ($type == 'json') {
                $content = json_decode(file_get_contents($file), true);
            } else {
                continue;
            }
            $content = $this->array_convert_keys_to_dot_notation($content);
            $total += count($content);

        }
        return $total;
    }


}
