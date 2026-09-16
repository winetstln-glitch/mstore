<?php
$viewsPath = "resources/views";
$excludeDirs = ["components", "layouts", "auth", "vendor", "errors"];

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsPath));
foreach ($it as $file) {
    if (!$file->isFile() || $file->getExtension() !== "php") continue;
    $path = $file->getPathname();
    
    $skip = false;
    foreach ($excludeDirs as $dir) {
        if (strpos(str_replace("\\\", "/", $path), "resources/views/$dir") !== false) {
            $skip = true;
            break;
        }
    }
    if ($skip) continue;

    $content = file_get_contents($path);
    $originalContent = $content;

    // Fix pagination
    $content = preg_replace("/(\{\{\s*\\$[a-zA-Z0-9_]+)->links\(\)\s*\}\}/", "$1->appends(request()->query())->links() }}", $content);
    $content = preg_replace("/(\{\!\!\s*\\$[a-zA-Z0-9_]+)->links\(\)\s*\!\!\})/", "$1->appends(request()->query())->links() !!}", $content);

    if ($content !== $originalContent) {
        file_put_contents($path, $content);
        echo "Fixed Pagination: $path\n";
    }
}

