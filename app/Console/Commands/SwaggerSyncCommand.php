<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

class SwaggerSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swagger:sync {--force : Force overwrite of existing annotations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically sync API documentation with existing routes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Swagger API documentation synchronization...');
        
        // Get all API routes
        $apiRoutes = $this->getApiRoutes();
        
        $this->info('Found ' . count($apiRoutes) . ' API routes');
        
        // Process each route to ensure it has Swagger annotations
        $processed = $this->processRoutes($apiRoutes);
        
        // Generate Swagger documentation
        $this->info('Generating Swagger documentation...');
        $result = $this->call('l5-swagger:generate');
        
        // Log the actions
        $this->logSyncActions($processed);
        
        // Summary
        $this->info('Swagger sync completed!');
        $this->table(['Action', 'Count'], [
            ['New Annotations Added', $processed['new']],
            ['Annotations Updated', $processed['updated']],
            ['Routes Processed', $processed['total']]
        ]);
        
        return 0;
    }
    
    /**
     * Get all API routes
     *
     * @return array
     */
    private function getApiRoutes()
    {
        $routes = [];
        $laravelRoutes = Route::getRoutes();
        
        foreach ($laravelRoutes as $route) {
            // Only include API routes that are defined in controllers
            if (Str::startsWith($route->uri, 'api/') && $route->getAction('controller')) {
                $action = $route->getAction('controller');
                
                if (is_string($action)) {
                    [$controller, $method] = Str::parseCallback($action, '__invoke');
                    
                    $routes[] = [
                        'uri' => $route->uri,
                        'methods' => $route->methods,
                        'controller' => $controller,
                        'method' => $method,
                        'name' => $route->getName()
                    ];
                }
            }
        }
        
        return $routes;
    }
    
    /**
     * Process routes to add/update Swagger annotations
     *
     * @param array $routes
     * @return array
     */
    private function processRoutes($routes)
    {
        $stats = [
            'new' => 0,
            'updated' => 0,
            'total' => count($routes)
        ];
        
        foreach ($routes as $route) {
            $controllerPath = $this->getControllerPath($route['controller']);
            
            if ($controllerPath && File::exists($controllerPath)) {
                $needsUpdate = $this->updateControllerMethodWithAnnotation($controllerPath, $route);
                
                if ($needsUpdate === 'new') {
                    $stats['new']++;
                } elseif ($needsUpdate === 'updated') {
                    $stats['updated']++;
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Get the file path for a controller
     *
     * @param string $controller
     * @return string|null
     */
    private function getControllerPath($controller)
    {
        // Convert namespace to file path
        $controller = str_replace('\\', '/', $controller);
        $controller = str_replace('App/Http/Controllers/', 'app/Http/Controllers/', $controller);
        $controller = base_path($controller . '.php');
        
        return File::exists($controller) ? $controller : null;
    }
    
    /**
     * Update a controller method with Swagger annotation if needed
     *
     * @param string $controllerPath
     * @param array $route
     * @return string|null 'new', 'updated', or null
     */
    private function updateControllerMethodWithAnnotation($controllerPath, $route)
    {
        $content = File::get($controllerPath);
        $methodName = $route['method'];
        
        // Check if method exists in the controller
        $controllerClass = str_replace('/', '\\', Str::after($route['controller'], 'App\\Http\\Controllers\\'));
        $fullControllerClass = 'App\\Http\\Controllers\\' . $controllerClass;
        
        // Use eval to dynamically check if class exists and has method
        if (!class_exists($fullControllerClass) || !method_exists($fullControllerClass, $methodName)) {
            return null;
        }
        
        $reflection = new ReflectionClass($fullControllerClass);
        $method = $reflection->getMethod($methodName);
        $docComment = $method->getDocComment();
        
        // Check if Swagger annotation already exists
        if ($docComment && strpos($docComment, '@OA\\') !== false) {
            // TODO: We could implement logic here to update existing annotations if the route signature changed
            return null;
        }
        
        // Generate new Swagger annotation
        $tag = $this->getTagForRoute($route);
        $swaggerAnnotation = $this->generateSwaggerAnnotation($route, $tag);
        
        // Get the method start line
        $methodStartLine = $method->getStartLine();
        $lines = explode("\n", $content);
        
        // Look for the method definition and add annotation before it
        $newContent = '';
        $annotationAdded = false;
        
        for ($i = 0; $i < count($lines); $i++) {
            if (!$annotationAdded && strpos($lines[$i], "function {$methodName}(") !== false) {
                // Add the annotation before this line
                $newContent .= $swaggerAnnotation . $lines[$i] . "\n";
                $annotationAdded = true;
            } else {
                $newContent .= $lines[$i] . "\n";
            }
        }
        
        // Write the updated content back to the file
        File::put($controllerPath, $newContent);
        
        return 'new';
    }
    
    /**
     * Get an appropriate tag for the route
     *
     * @param array $route
     * @return string
     */
    private function getTagForRoute($route)
    {
        // Extract tag from URI (e.g., 'users', 'properties', etc.)
        $segments = explode('/', $route['uri']);
        $tag = $segments[1] ?? 'general'; // Second segment after 'api'
        
        // Make it singular if it's plural
        if (Str::endsWith($tag, ['s', 'es'])) {
            $tag = Str::singular($tag);
        }
        
        return ucfirst($tag);
    }
    
    /**
     * Generate Swagger annotation for a route
     *
     * @param array $route
     * @param string $tag
     * @return string
     * Generate a summary for the method based on HTTP verb and path
     *
     * @param string $method
     * @param string $path
     * @return string
     */
    private function generateSummary($method, $path)
    {
        $pathParts = explode('/', $path);
        $resource = $pathParts[count($pathParts) - 1];
        
        if ($resource === '{id}') {
            $resource = Str::singular($pathParts[count($pathParts) - 2]);
        } elseif (is_numeric($resource)) {
            $resource = Str::singular($pathParts[count($pathParts) - 2]);
        }
        
        $resource = Str::ucfirst($resource);
        
        switch ($method) {
            case 'get':
                if (strpos($path, '{id}') !== false || is_numeric(array_pop($pathParts))) {
                    return "Get a {$resource} by ID";
                } else {
                    return "Get all {$resource}";
                }
            case 'post':
                return "Create a new {$resource}";
            case 'put':
            case 'patch':
                return "Update a {$resource} by ID";
            case 'delete':
                return "Delete a {$resource} by ID";
            default:
                return "Handle {$resource} request";
        }
    }
    
    /**
     * Log sync actions
     *
     * @param array $processed
     * @return void
     */
    private function logSyncActions($processed)
    {
        $logMessage = '[' . now()->toISOString() . "] Swagger sync: {$processed['new']} new annotations, {$processed['updated']} updated annotations\n";
        File::append(storage_path('logs/swagger-sync.log'), $logMessage);
    }
}
