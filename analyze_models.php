<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$models = [];
$modelFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/app/Models'));
foreach ($modelFiles as $file) {
    if ($file->getExtension() === 'php') {
        $className = 'App\\Models\\' . str_replace('/', '\\', str_replace(__DIR__.'/app/Models/', '', substr($file->getPathname(), 0, -4)));
        if (class_exists($className) && is_subclass_of($className, 'Illuminate\Database\Eloquent\Model')) {
            try {
                $reflection = new ReflectionClass($className);
                if ($reflection->isAbstract()) continue;
                $instance = new $className;
                $relationships = [];
                foreach ($reflection->getMethods() as $method) {
                    if ($method->class === $className && $method->getNumberOfParameters() == 0) {
                        try {
                            $returnType = $method->getReturnType();
                            if ($returnType && strpos($returnType->getName(), 'Illuminate\Database\Eloquent\Relations') !== false) {
                                $relationships[] = $method->getName() . ' (' . class_basename($returnType->getName()) . ')';
                            }
                        } catch (\Throwable $e) {}
                    }
                }
                
                $models[$className] = [
                    'table' => $instance->getTable(),
                    'fillable' => $instance->getFillable(),
                    'guarded' => $instance->getGuarded(),
                    'casts' => $instance->getCasts(),
                    'with' => $reflection->getProperty('with')->hasDefaultValue() ? $reflection->getDefaultProperties()['with'] ?? [] : [],
                    'relationships' => $relationships
                ];
            } catch (\Throwable $e) {}
        }
    }
}
file_put_contents('models_analysis.json', json_encode($models, JSON_PRETTY_PRINT));
echo "Models analyzed. Count: " . count($models) . "\n";
