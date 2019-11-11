<?php

namespace Bond211\SassCompiler;

use ScssPhp\ScssPhp\Compiler;

class SassCompiler
{
    /**
     * Compiles all .scss files in a given folder into .css files in a given folder
     *
     * @param string $scssDir source folder where you have your .scss files
     * @param string $cssDir destination folder where you want to store your .css files
     * @param int $mode
     * @param string $format
     */
    public static function run($scssDir, $cssDir, $mode = Mode::DEFAULT, $format = OutputFormat::COMPRESSED)
    {
        $files = glob($scssDir . '[^_]*.scss');

        if ($files === false) {
            return;
        }

        $lastIncludeModifiedTimestamp = self::toLastIncludeModifiedTimestamp($mode, $scssDir);

        // loop through .scss files and check if any needs recompilation
        foreach ($files as $sassFilename) {
            $cssFilename = str_replace([$scssDir, '.scss'], [$cssDir, '.css'], $sassFilename);

            if (!self::shouldCompile($mode, $sassFilename, $cssFilename, $lastIncludeModifiedTimestamp)) {
                continue;
            }

            self::compile($sassFilename, $scssDir, $cssDir, $format);
        }
    }

    private static function shouldCompile(
        string $mode,
        string $sassFilename,
        string $cssFilename,
        int $lastIncludeModifiedTimestamp
    )
    {
        // force mode
        if ($mode === Mode::FORCE) {
            return true;
        }

        // target file does not exist
        if (!self::fileExists($cssFilename)) {
            return true;
        }

        $sourceFileModificationTimestamp = filemtime($sassFilename);
        $targetFileModificationTimestamp = filemtime($sassFilename);

        // source file modified after target file
        if ($sourceFileModificationTimestamp >= $targetFileModificationTimestamp) {
            return true;
        }

        // check include files
        if ($mode === Mode::CHECK_INCLUDES) {
            // check include modification timestamp
            return $lastIncludeModifiedTimestamp >= $sourceFileModificationTimestamp;
        }

        return false;
    }

    private static function toCompiler(string $scssFolder, string $formatter)
    {
        static $compiler = null;

        if ($compiler !== null) {
            return $compiler;
        }

        $compiler = new Compiler();

        // set the path where your _mixins are
        $compiler->setImportPaths([
            $scssFolder,
            '../node_modules/',
        ]);
        $compiler->setFormatter('ScssPhp\\ScssPhp\\Formatter\\' . $formatter);

        return $compiler;
    }

    private static function toLastIncludeModifiedTimestamp(string $mode, string $scssDir): int
    {
        $lastModifiedTimestamp = 0;

        if ($mode !== Mode::CHECK_INCLUDES) {
            return $lastModifiedTimestamp;
        }

        $includeFiles = glob($scssDir . '_*.scss');

        foreach ($includeFiles as $file) {
            $time = filemtime($file);

            if (!$time) {
                continue;
            }

            $lastModifiedTimestamp = max($lastModifiedTimestamp, $time);
        }

        return $lastModifiedTimestamp;
    }

    /**
     * @param $path
     * @return bool
     */
    private static function fileExists($path): bool
    {
        return realpath($path);
    }

    private static function compile($sassFilename, $scssDir, $cssDir, $formatter)
    {
        $scssCompiler = self::toCompiler($scssDir, $formatter);

        // get scss and css paths
        $cssFilename = str_replace([$scssDir, '.scss'], [$cssDir, '.css'], $sassFilename);

        // get .scss's content, put it into $stringSass
        $stringSass = file_get_contents($sassFilename);

        // tilde support
        $stringSass = str_replace('@import "~', '@import "../node_modules/', $stringSass);

        // compile this SASS code to CSS
        $string_css = $scssCompiler->compile($stringSass);

        // write CSS into file with the same filename, but with .css extension
        file_put_contents($cssFilename, '/* ' . strftime('%Y-%m-%d %H:%M:%S') . ' */' . "\n" . $string_css);
    }
}
