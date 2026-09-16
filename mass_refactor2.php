<?php
$controllersPath = "app/Http/Controllers";
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($controllersPath));
foreach ($it as $file) {
    if ($file->isFile() && $file->getExtension() === "php") {
        $content = file_get_contents($file->getPathname());
        $newContent = str_replace("->paginate()", "->customPaginate()", $content);
        if ($newContent !== $content) {
            file_put_contents($file->getPathname(), $newContent);
            echo "Updated Controller (empty paginate): " . $file->getPathname() . "\n";
        }
    }
}

