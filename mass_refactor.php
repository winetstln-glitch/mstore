<?php
$controllersPath = "app/Http/Controllers";
$viewsPath = "resources/views";

// 1. Replace ->paginate(10) with ->customPaginate() in all controllers
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($controllersPath));
foreach ($it as $file) {
    if ($file->isFile() && $file->getExtension() === "php") {
        $content = file_get_contents($file->getPathname());
        $newContent = preg_replace("/->paginate\([0-9]+\)/", "->customPaginate()", $content);
        if ($newContent !== $content) {
            file_put_contents($file->getPathname(), $newContent);
            echo "Updated Controller: " . $file->getPathname() . "\n";
        }
    }
}

