<?php

function parseModel($file) {
    $content = file_get_contents($file);
    $res = [
        'file' => basename($file),
        'table' => null,
        'fillable' => false,
        'guarded' => false,
        'mass_assignment_risk' => false,
        'relationships' => [],
        'eager_loading' => [],
    ];

    if (preg_match('/protected\s+\$table\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $m)) $res['table'] = $m[1];
    if (preg_match('/protected\s+\$fillable\s*=/', $content)) $res['fillable'] = true;
    if (preg_match('/protected\s+\$guarded\s*=/', $content)) $res['guarded'] = true;

    if (!$res['fillable'] && !$res['guarded']) {
        $res['mass_assignment_risk'] = true;
    }

    if (preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(\)\s*(?::\s*[a-zA-Z0-9_\\\\]+)?\s*\{[^\}]+->(hasMany|belongsTo|hasOne|belongsToMany|morphTo|morphMany|morphOne|hasManyThrough)/', $content, $m)) {
        foreach($m[1] as $idx => $rel) {
            $res['relationships'][] = $rel . ' (' . $m[2][$idx] . ')';
        }
    }

    if (preg_match_all('/\$with\s*=\s*\[([^\]]+)\]/', $content, $m)) {
        $res['eager_loading'] = $m[1];
    }
    
    return $res;
}

function parseMigration($file) {
    $content = file_get_contents($file);
    $tables = [];
    if (preg_match_all('/Schema::create\([\'"]([^\'"]+)[\'"]/', $content, $m)) {
        foreach($m[1] as $t) {
            preg_match_all('/->foreignId\([\'"]([^\'"]+)[\'"]\)/', $content, $fk_matches1);
            preg_match_all('/->foreign\([\'"]([^\'"]+)[\'"]\)/', $content, $fk_matches2);
            $fks = array_merge($fk_matches1[1], $fk_matches2[1]);
            
            // Checking if ->constrained() is used or foreign references are defined
            $has_constrained = preg_match('/->constrained\(/', $content);
            $has_references = preg_match('/->references\(/', $content);
            
            preg_match_all('/->(?:string|integer|bigInteger|text|boolean|date|timestamp)[\s\S]*?->index\(\)/', $content, $idx_matches);
            
            $tables[$t] = [
                'file' => basename($file),
                'fks' => $fks,
                'has_foreign_constraints' => $has_constrained || $has_references,
                'has_indexes' => count($idx_matches[0]) > 0
            ];
        }
    }
    return $tables;
}

$models = [];
$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/app/Models'));
foreach ($dir as $file) {
    if ($file->getExtension() === 'php') {
        $models[] = parseModel($file->getPathname());
    }
}

$migrations = [];
$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/database/migrations'));
foreach ($dir as $file) {
    if ($file->getExtension() === 'php') {
        $parsed = parseMigration($file->getPathname());
        foreach ($parsed as $t => $info) {
            $migrations[$t] = $info;
        }
    }
}

file_put_contents('analysis_results.json', json_encode(['models' => $models, 'migrations' => $migrations], JSON_PRETTY_PRINT));
echo "Done.\n";
