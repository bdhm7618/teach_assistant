<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;

class GenerateApiDocs extends Command
{
    protected $signature = 'api:docs:generate';
    protected $description = 'Generate API documentation as JSON from all routes';

    public function handle()
    {
        $this->info('Generating API documentation...');

        $routes = $this->getApiRoutes();
        $documentation = $this->buildDocumentation($routes);

        $path = storage_path('api-docs/api-documentation.json');
        File::ensureDirectoryExists(storage_path('api-docs'));
        
        $json = json_encode($documentation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        if ($json === false) {
            $this->error('Failed to encode JSON: ' . json_last_error_msg());
            return 1;
        }
        
        File::put($path, $json);

        $this->info("API documentation generated successfully at: {$path}");
        $this->info("Total endpoints: " . count($routes));

        return 0;
    }

    protected function getApiRoutes()
    {
        $routes = [];
        
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            
            // Only include API routes
            if (str_starts_with($uri, 'api/') || str_contains($uri, '/api/')) {
                $action = $route->getAction();
                $controller = $action['controller'] ?? null;
                
                if ($controller) {
                    [$controllerClass, $method] = explode('@', $controller);
                } else {
                    $controllerClass = $action['uses'] ?? null;
                    $method = null;
                }

                $routes[] = [
                    'httpMethods' => $route->methods(),
                    'uri' => '/' . $uri,
                    'name' => $route->getName(),
                    'controller' => $controllerClass,
                    'controllerMethod' => $method,
                    'middleware' => $route->gatherMiddleware(),
                ];
            }
        }

        return $routes;
    }

    protected function buildDocumentation($routes)
    {
        $documentation = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Teachify API Documentation',
                'version' => '1.0.0',
                'description' => 'API documentation for Teachify application - A comprehensive educational platform API',
            ],
            'servers' => [
                [
                    'url' => url('/'),
                    'description' => 'API Server',
                ],
            ],
            'paths' => [],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                        'description' => 'Enter JWT token',
                    ],
                ],
                'schemas' => [
                    'SuccessResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => [
                                'type' => 'string',
                                'example' => 'success',
                            ],
                            'message' => [
                                'type' => 'string',
                                'example' => 'Operation successful',
                            ],
                            'data' => [
                                'type' => 'object',
                            ],
                        ],
                    ],
                    'ErrorResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => [
                                'type' => 'string',
                                'example' => 'error',
                            ],
                            'message' => [
                                'type' => 'string',
                                'example' => 'Error message',
                            ],
                            'errors' => [
                                'type' => 'object',
                                'nullable' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($routes as $route) {
            $path = $this->convertUriToOpenApiPath($route['uri']);
            $httpMethods = $route['httpMethods'];
            
            foreach ($httpMethods as $httpMethod) {
                $httpMethod = strtolower($httpMethod);
                
                // Skip HEAD and OPTIONS methods
                if (in_array($httpMethod, ['head', 'options'])) {
                    continue;
                }
                
                if (!isset($documentation['paths'][$path])) {
                    $documentation['paths'][$path] = [];
                }

                $operation = [
                    'tags' => $this->extractTags($route),
                    'summary' => $this->generateSummary($route, $httpMethod),
                    'description' => $this->generateDescription($route, $httpMethod),
                    'operationId' => $this->generateOperationId($route, $httpMethod),
                ];

            // Add security if middleware contains auth
            if (in_array('auth:user', $route['middleware']) || in_array('auth:admin', $route['middleware'])) {
                $operation['security'] = [['bearerAuth' => []]];
            }

            // Add parameters for path variables
            $parameters = $this->extractParameters($route['uri']);
            if (!empty($parameters)) {
                $operation['parameters'] = $parameters;
            }

                // Add request body for POST, PUT, PATCH
                if (in_array($httpMethod, ['post', 'put', 'patch'])) {
                    $requestBody = $this->extractRequestBody($route, $httpMethod);
                    if ($requestBody) {
                        $operation['requestBody'] = $requestBody;
                    } else {
                        $operation['requestBody'] = [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                    ],
                                ],
                            ],
                        ];
                    }
                }

                // Add responses
                $responseSchema = $this->extractResponseSchema($route, $httpMethod);
                $responseExample = $this->generateResponseExample($responseSchema);
                
                $operation['responses'] = [
                    '200' => [
                        'description' => 'Success',
                        'content' => [
                            'application/json' => [
                                'schema' => $responseSchema ?: ['$ref' => '#/components/schemas/SuccessResponse'],
                                'example' => $responseExample,
                            ],
                        ],
                    ],
                    '422' => [
                        'description' => 'Validation error',
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/ErrorResponse'],
                                'example' => [
                                    'status' => 'error',
                                    'message' => 'Validation failed',
                                    'errors' => [
                                        'field' => ['The field is required.']
                                    ]
                                ],
                            ],
                        ],
                    ],
                ];

                if (in_array($httpMethod, ['post', 'put'])) {
                    $createdExample = $this->generateResponseExample($responseSchema, 201);
                    $operation['responses']['201'] = [
                        'description' => 'Created',
                        'content' => [
                            'application/json' => [
                                'schema' => $responseSchema ?: ['$ref' => '#/components/schemas/SuccessResponse'],
                                'example' => $createdExample,
                            ],
                        ],
                    ];
                }

                $documentation['paths'][$path][$httpMethod] = $operation;
            }
        }

        // Generate tags after all paths are processed
        $documentation['tags'] = $this->generateTags($routes);
        
        return $documentation;
    }

    protected function convertUriToOpenApiPath($uri)
    {
        // Convert Laravel route parameters to OpenAPI format
        return preg_replace('/\{(\w+)\}/', '{$1}', $uri);
    }

    protected function extractTags($route)
    {
        $uri = $route['uri'];
        $tags = [];

        // Extract Package (module/prefix)
        $package = $this->extractPackage($uri);
        
        // Extract Feature (resource name)
        $feature = $this->extractFeature($uri, $route);
        
        // Format: "Package - Feature" for better organization
        if ($package && $feature) {
            $tags[] = "{$package} - {$feature}";
        } elseif ($package) {
            $tags[] = $package;
        } else {
            $tags[] = 'General';
        }

        return $tags;
    }

    /**
     * Extract package name from URI
     * Examples: /api/v1/academic/... -> Academic, /api/v1/channel/... -> Channel
     */
    protected function extractPackage($uri)
    {
        // Match patterns like /api/v1/academic/, /api/v1/channel/, /api/v1/admin/
        if (preg_match('/\/api\/v\d+\/([^\/]+)/', $uri, $matches)) {
            $package = $matches[1];
            
            // Map common package names to readable format
            $packageMap = [
                'academic' => 'Academic',
                'channel' => 'Channel',
                'admin' => 'Admin',
                'student' => 'Student',
                'students' => 'Student',
            ];
            
            return $packageMap[strtolower($package)] ?? ucfirst($package);
        }
        
        return null;
    }

    /**
     * Extract feature name from URI
     * Examples: /api/v1/academic/groups -> Groups, /api/v1/academic/subjects -> Subjects
     */
    protected function extractFeature($uri, $route)
    {
        // Try to extract from controller name first
        $controller = $route['controller'] ?? null;
        if ($controller) {
            $controllerName = class_basename($controller);
            // Remove "Controller" suffix and extract resource name
            if (preg_match('/(\w+)Controller$/', $controllerName, $matches)) {
                $resource = $matches[1];
                
                // Map controller names to feature names
                $featureMap = [
                    'Group' => 'Groups',
                    'Subject' => 'Subjects',
                    'ClassGrade' => 'Class Grades',
                    'Student' => 'Students',
                    'GroupMetadata' => 'Groups Metadata',
                    'StudentMetadata' => 'Students Metadata',
                ];
                
                if (isset($featureMap[$resource])) {
                    return $featureMap[$resource];
                }
                
                // Pluralize if not in map
                return $resource . 's';
            }
        }
        
        // Fallback: extract from URI path
        $parts = explode('/', trim($uri, '/'));
        
        // Look for resource names after package prefix
        // Pattern: /api/v1/academic/groups -> groups
        foreach ($parts as $index => $part) {
            // Skip api, v1, and package name
            if (in_array($part, ['api', 'v1', 'v2']) || 
                in_array(strtolower($part), ['academic', 'channel', 'admin', 'student', 'students'])) {
                continue;
            }
            
            // Skip path parameters like {id}, {group}, etc.
            if (str_starts_with($part, '{')) {
                continue;
            }
            
            // Found a resource name
            $feature = str_replace('-', ' ', $part);
            $feature = ucwords($feature);
            
            // Map common feature names
            $featureMap = [
                'Groups' => 'Groups',
                'Subjects' => 'Subjects',
                'Class Grades' => 'Class Grades',
                'Students' => 'Students',
                'Groups Metadata' => 'Groups Metadata',
                'Students Metadata' => 'Students Metadata',
                'User' => 'User',
                'Login' => 'Authentication',
                'Register' => 'Authentication',
            ];
            
            return $featureMap[$feature] ?? $feature;
        }
        
        return null;
    }

    /**
     * Generate tags list for OpenAPI documentation
     * This creates a comprehensive list of all tags used in the API
     */
    protected function generateTags($routes)
    {
        $tagsMap = [];
        
        foreach ($routes as $route) {
            $routeTags = $this->extractTags($route);
            foreach ($routeTags as $tag) {
                if (!isset($tagsMap[$tag])) {
                    // Extract package and feature from tag
                    $parts = explode(' - ', $tag);
                    $package = $parts[0] ?? $tag;
                    $feature = $parts[1] ?? null;
                    
                    $tagsMap[$tag] = [
                        'name' => $tag,
                        'description' => $feature 
                            ? "{$feature} operations in {$package} package"
                            : "Operations in {$package} package",
                    ];
                }
            }
        }
        
        // Sort tags by package, then by feature
        uksort($tagsMap, function ($a, $b) {
            $aParts = explode(' - ', $a);
            $bParts = explode(' - ', $b);
            $aPackage = $aParts[0] ?? $a;
            $bPackage = $bParts[0] ?? $b;
            
            if ($aPackage !== $bPackage) {
                return strcmp($aPackage, $bPackage);
            }
            
            $aFeature = $aParts[1] ?? '';
            $bFeature = $bParts[1] ?? '';
            return strcmp($aFeature, $bFeature);
        });
        
        return array_values($tagsMap);
    }

    protected function generateSummary($route, $httpMethod)
    {
        $uri = $route['uri'];
        $methodName = $route['method'] ?? '';

        if ($methodName) {
            return ucfirst($methodName);
        }

        $parts = explode('/', trim($uri, '/'));
        $lastPart = end($parts);
        
        $action = ucfirst($httpMethod);
        $resource = ucfirst(str_replace('-', ' ', $lastPart));
        
        return "{$action} {$resource}";
    }

    protected function generateDescription($route, $httpMethod)
    {
        $uri = $route['uri'];
        
        $descriptions = [
            'get' => 'Retrieve',
            'post' => 'Create',
            'put' => 'Update',
            'patch' => 'Update',
            'delete' => 'Delete',
        ];

        $action = $descriptions[$httpMethod] ?? 'Process';
        return "{$action} resource";
    }

    protected function generateOperationId($route, $httpMethod)
    {
        $uri = $route['uri'];
        $methodName = $route['method'] ?? '';

        if ($methodName) {
            return $httpMethod . '_' . lcfirst($methodName);
        }

        $parts = array_filter(explode('/', trim($uri, '/')));
        $operationId = $httpMethod . '_' . implode('_', array_map('ucfirst', $parts));
        
        return str_replace(['-', '{', '}'], ['_', '', ''], $operationId);
    }

    protected function extractParameters($uri)
    {
        $parameters = [];
        preg_match_all('/\{(\w+)\}/', $uri, $matches);

        foreach ($matches[1] as $param) {
            $parameters[] = [
                'name' => $param,
                'in' => 'path',
                'required' => true,
                'schema' => [
                    'type' => 'integer',
                ],
                'description' => "The {$param} identifier",
            ];
        }

        return $parameters;
    }

    protected function extractRequestBody($route, $httpMethod)
    {
        $controller = $route['controller'] ?? null;
        $method = $route['controllerMethod'] ?? null;

        if (!$controller || !$method || !class_exists($controller)) {
            return null;
        }

        try {
            $reflection = new ReflectionClass($controller);
            $methodReflection = $reflection->getMethod($method);
            $parameters = $methodReflection->getParameters();

            // First, try to find FormRequest in parameters
            foreach ($parameters as $parameter) {
                $type = $parameter->getType();
                if ($type && !$type->isBuiltin()) {
                    $typeName = $type->getName();
                    if (is_subclass_of($typeName, FormRequest::class)) {
                        return $this->buildRequestBodyFromRequest($typeName);
                    }
                }
            }

            // If no FormRequest found, try to extract validation from method body
            $requestBody = $this->extractValidationFromMethod($reflection, $methodReflection);
            if ($requestBody) {
                return $requestBody;
            }
        } catch (\Exception $e) {
            // Ignore reflection errors
        }

        return null;
    }

    protected function extractValidationFromMethod($reflection, $methodReflection)
    {
        try {
            $methodCode = file_get_contents($reflection->getFileName());
            $startLine = $methodReflection->getStartLine();
            $endLine = $methodReflection->getEndLine();
            $methodLines = array_slice(explode("\n", $methodCode), $startLine - 1, $endLine - $startLine + 1);
            $methodContent = implode("\n", $methodLines);

            // Look for $request->validate([...]) pattern
            if (preg_match('/\$request->validate\s*\(\s*\[(.*?)\]\s*\)/s', $methodContent, $matches)) {
                $validationArray = $matches[1];
                
                // Parse the validation array
                $rules = $this->parseValidationArray($validationArray);
                
                if (!empty($rules)) {
                    return $this->buildRequestBodyFromRules($rules);
                }
            }

            // Also look for validate([...]) pattern
            if (preg_match('/validate\s*\(\s*\[(.*?)\]\s*\)/s', $methodContent, $matches)) {
                $validationArray = $matches[1];
                
                // Parse the validation array
                $rules = $this->parseValidationArray($validationArray);
                
                if (!empty($rules)) {
                    return $this->buildRequestBodyFromRules($rules);
                }
            }
        } catch (\Exception $e) {
            // Ignore parsing errors
        }

        return null;
    }

    protected function parseValidationArray($arrayContent)
    {
        $rules = [];
        
        // Remove comments
        $arrayContent = preg_replace('/\/\/.*$/m', '', $arrayContent);
        
        // Pattern 1: 'field' => 'rule1|rule2' or "field" => "rule1|rule2"
        if (preg_match_all('/[\'"]([^\'"]+)[\'"]\s*=>\s*[\'"]([^\'"]+)[\'"]/', $arrayContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $field = $match[1];
                $rule = $match[2];
                $rules[$field] = $rule;
            }
        }
        
        // Pattern 2: 'field' => ['rule1', 'rule2'] or "field" => ["rule1", "rule2"]
        if (preg_match_all('/[\'"]([^\'"]+)[\'"]\s*=>\s*\[(.*?)\]/s', $arrayContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $field = $match[1];
                $ruleArray = $match[2];
                // Extract individual rules from array
                if (preg_match_all('/[\'"]([^\'"]+)[\'"]/', $ruleArray, $ruleMatches)) {
                    $rules[$field] = $ruleMatches[1];
                }
            }
        }

        return $rules;
    }

    protected function buildRequestBodyFromRules($rules)
    {
        $properties = [];
        $required = [];
        $arrayFields = [];

        foreach ($rules as $field => $rule) {
            // Check if this is an array field (e.g., student_ids.*, session_times.*.day)
            if (str_contains($field, '.*')) {
                $baseField = explode('.*', $field)[0];
                
                // Check if this is a nested array field (e.g., session_times.*.day)
                $parts = explode('.', $field);
                if (count($parts) > 2) {
                    // This is a nested array field like session_times.*.day
                    $arrayFields[$baseField] = [
                        'type' => 'array',
                        'items' => ['type' => 'object'],
                        'nested' => true,
                    ];
                    
                    // Store nested field info
                    if (!isset($arrayFields[$baseField]['nestedFields'])) {
                        $arrayFields[$baseField]['nestedFields'] = [];
                    }
                    
                    $nestedField = $parts[2];
                    $rulesArray = is_array($rule) ? $rule : explode('|', $rule);
                    $stringRules = array_filter($rulesArray, function($r) {
                        return is_string($r);
                    });
                    
                    if (!empty($stringRules)) {
                        $fieldSchema = $this->parseValidationRule($stringRules);
                        $arrayFields[$baseField]['nestedFields'][$nestedField] = $fieldSchema;
                    }
                } else {
                    // This is a simple array field like student_ids.*
                    $rulesArray = is_array($rule) ? $rule : explode('|', $rule);
                    $stringRules = array_filter($rulesArray, function($r) {
                        return is_string($r);
                    });
                    
                    // If no string rules (only objects), assume integer type for IDs
                    if (empty($stringRules)) {
                        $itemSchema = ['type' => 'integer'];
                    } else {
                        $itemSchema = $this->parseValidationRule($stringRules);
                    }
                    
                    $arrayFields[$baseField] = [
                        'type' => 'array',
                        'items' => $itemSchema,
                        'nested' => false,
                    ];
                }
                continue;
            }
            
            $rulesArray = is_array($rule) ? $rule : explode('|', $rule);
            
            // Filter out objects and keep only string rules
            $stringRules = array_filter($rulesArray, function($r) {
                return is_string($r);
            });
            
            if (empty($stringRules)) {
                // If no string rules, use default string type
                $properties[$field] = ['type' => 'string'];
            } else {
                $fieldSchema = $this->parseValidationRule($stringRules);
                if ($fieldSchema) {
                    $properties[$field] = $fieldSchema;
                }
            }
            
            // Check if field is required
            if (in_array('required', $rulesArray) || (is_string($rule) && str_contains($rule, 'required'))) {
                $required[] = $field;
            }
        }

        // Merge array fields into properties
        foreach ($arrayFields as $field => $arrayConfig) {
            if ($arrayConfig['nested']) {
                // Nested array (e.g., session_times)
                $nestedProperties = $arrayConfig['nestedFields'] ?? [];
                // Ensure all required fields are present for session_times
                if ($field === 'session_times') {
                    if (!isset($nestedProperties['day'])) {
                        $nestedProperties['day'] = ['type' => 'string', 'enum' => ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday']];
                    }
                    if (!isset($nestedProperties['start_time'])) {
                        $nestedProperties['start_time'] = ['type' => 'string', 'format' => 'date_format:H:i'];
                    }
                    if (!isset($nestedProperties['end_time'])) {
                        $nestedProperties['end_time'] = ['type' => 'string', 'format' => 'date_format:H:i'];
                    }
                    if (!isset($nestedProperties['is_active'])) {
                        $nestedProperties['is_active'] = ['type' => 'boolean'];
                    }
                }
                $properties[$field] = [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => $nestedProperties,
                    ],
                ];
            } else {
                // Simple array (e.g., student_ids)
                // Remove 'nested' key from final schema
                unset($arrayConfig['nested']);
                $properties[$field] = $arrayConfig;
            }
        }

        if (empty($properties)) {
            return null;
        }

        return [
            'required' => !empty($required),
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'required' => $required,
                        'properties' => $properties,
                    ],
                    'example' => $this->generateExampleFromProperties($properties),
                ],
            ],
        ];
    }

    protected function buildRequestBodyFromRequest($requestClass)
    {
        if (!class_exists($requestClass)) {
            return null;
        }

        try {
            $request = new $requestClass();
            $rules = $request->rules();
            
            return $this->buildRequestBodyFromRules($rules);
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function parseValidationRule($rules)
    {
        $schema = [
            'type' => 'string',
        ];

        foreach ($rules as $rule) {
            // Skip if rule is an object (custom validation rules)
            if (!is_string($rule)) {
                continue;
            }

            if (str_contains($rule, 'integer')) {
                $schema['type'] = 'integer';
            } elseif (str_contains($rule, 'boolean')) {
                $schema['type'] = 'boolean';
            } elseif (str_contains($rule, 'numeric') || str_contains($rule, 'decimal')) {
                $schema['type'] = 'number';
            } elseif (str_contains($rule, 'array')) {
                $schema['type'] = 'array';
            } elseif (str_contains($rule, 'email')) {
                $schema['format'] = 'email';
            } elseif (str_contains($rule, 'date_format:H:i') || (str_contains($rule, 'date_format') && str_contains($rule, 'H:i'))) {
                $schema['format'] = 'date_format:H:i';
            } elseif (str_contains($rule, 'date')) {
                $schema['format'] = 'date';
            } elseif (preg_match('/max:(\d+)/', $rule, $matches)) {
                $schema['maxLength'] = (int)$matches[1];
            } elseif (preg_match('/min:(\d+)/', $rule, $matches)) {
                $schema['minLength'] = (int)$matches[1];
            } elseif (preg_match('/in:(.+)/', $rule, $matches)) {
                $schema['enum'] = explode(',', $matches[1]);
            }
        }

        return $schema;
    }

    protected function generateExampleFromProperties($properties)
    {
        $example = [];
        
        // Field-specific examples
        $fieldExamples = [
            'email' => 'user@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone' => '+1234567890',
            'name' => 'John Doe',
            'channel_name' => 'My Channel',
            'otp' => '123456',
            'code' => '123456',
            'start_year' => 2024,
            'end_year' => 2025,
            'grade_level' => 1,
            'stage' => 'primary',
            'class_grade_id' => 1,
        ];
        
        foreach ($properties as $field => $schema) {
            // Check if we have a specific example for this field
            if (isset($fieldExamples[$field])) {
                $example[$field] = $fieldExamples[$field];
                continue;
            }
            
            $type = $schema['type'] ?? 'string';
            
            switch ($type) {
                case 'integer':
                    // Use min value if available, otherwise default
                    $example[$field] = $schema['minLength'] ?? 1;
                    break;
                case 'boolean':
                    $example[$field] = true;
                    break;
                case 'number':
                    $example[$field] = 100.50;
                    break;
                case 'array':
                    // Handle array types
                    if (isset($schema['items'])) {
                        $itemsType = $schema['items']['type'] ?? 'string';
                        
                        if ($itemsType === 'object' && isset($schema['items']['properties'])) {
                            // Nested array of objects (e.g., session_times)
                            $nestedExample = [];
                            foreach ($schema['items']['properties'] as $nestedField => $nestedSchema) {
                                $nestedExample[$nestedField] = $this->getExampleValueForSchema($nestedSchema, $nestedField);
                            }
                            // Ensure all required fields are present for session_times
                            if ($field === 'session_times') {
                                $nestedExample['day'] = $nestedExample['day'] ?? 'monday';
                                $nestedExample['start_time'] = $nestedExample['start_time'] ?? '09:00';
                                $nestedExample['end_time'] = $nestedExample['end_time'] ?? '10:30';
                                $nestedExample['is_active'] = $nestedExample['is_active'] ?? true;
                            }
                            $example[$field] = [$nestedExample];
                        } else {
                            // Simple array (e.g., student_ids, group_ids)
                            $itemExample = $this->getExampleValueForSchema($schema['items'], '');
                            // For ID arrays, provide multiple examples
                            if ($itemsType === 'integer' || (isset($schema['items']['type']) && $schema['items']['type'] === 'integer')) {
                                $example[$field] = [1, 2];
                            } else {
                                $example[$field] = [$itemExample];
                            }
                        }
                    } else {
                        $example[$field] = [];
                    }
                    break;
                default:
                    if (isset($schema['enum'])) {
                        $example[$field] = $schema['enum'][0];
                    } elseif (isset($schema['format'])) {
                        if ($schema['format'] === 'email') {
                            $example[$field] = 'user@example.com';
                        } elseif ($schema['format'] === 'date' || $schema['format'] === 'date-time') {
                            $example[$field] = '2024-01-01';
                        } elseif ($schema['format'] === 'date_format:H:i') {
                            $example[$field] = '09:00';
                        } else {
                            $example[$field] = 'example';
                        }
                    } else {
                        $example[$field] = 'example';
                    }
            }
        }
        
        return $example;
    }

    protected function getExampleValueForSchema($schema, $fieldName = '')
    {
        $type = $schema['type'] ?? 'string';
        
        // Field-specific examples
        $fieldExamples = [
            'day' => 'monday',
            'start_time' => '09:00',
            'end_time' => '10:30',
            'is_active' => true,
        ];
        
        if (isset($fieldExamples[$fieldName])) {
            return $fieldExamples[$fieldName];
        }
        
        switch ($type) {
            case 'integer':
                return $schema['minLength'] ?? 1;
            case 'boolean':
                return true;
            case 'number':
                return 100.50;
            default:
                if (isset($schema['enum'])) {
                    return $schema['enum'][0];
                } elseif (isset($schema['format'])) {
                    if ($schema['format'] === 'email') {
                        return 'user@example.com';
                    } elseif ($schema['format'] === 'date' || $schema['format'] === 'date-time') {
                        return '2024-01-01';
                    } elseif ($schema['format'] === 'date_format:H:i' || str_contains($schema['format'] ?? '', 'H:i')) {
                        return '09:00';
                    }
                }
                // Check field name for hints
                if (str_contains($fieldName, 'time') && str_contains($fieldName, 'start')) {
                    return '09:00';
                } elseif (str_contains($fieldName, 'time') && str_contains($fieldName, 'end')) {
                    return '10:30';
                } elseif (str_contains($fieldName, 'day')) {
                    return 'monday';
                }
                return 'example';
        }
    }

    protected function extractResponseSchema($route, $httpMethod)
    {
        $controller = $route['controller'] ?? null;
        $method = $route['controllerMethod'] ?? null;

        if (!$controller || !$method || !class_exists($controller)) {
            return null;
        }

        try {
            $reflection = new ReflectionClass($controller);
            
            // Check if controller has getResource method (BaseController pattern)
            if ($reflection->hasMethod('getResource')) {
                $methodReflection = $reflection->getMethod('getResource');
                $methodReflection->setAccessible(true);
                $controllerInstance = $reflection->newInstanceWithoutConstructor();
                $resourceClass = $methodReflection->invoke($controllerInstance);
                if ($resourceClass && class_exists($resourceClass) && is_subclass_of($resourceClass, JsonResource::class)) {
                    return $this->buildResponseSchemaFromResource($resourceClass);
                }
            }

            // Try to find Resource usage in method
            $methodReflection = $reflection->getMethod($method);
            $methodCode = file_get_contents($reflection->getFileName());
            $startLine = $methodReflection->getStartLine();
            $endLine = $methodReflection->getEndLine();
            $methodLines = array_slice(explode("\n", $methodCode), $startLine - 1, $endLine - $startLine + 1);
            $methodContent = implode("\n", $methodLines);

            // Look for Resource class usage patterns
            // Pattern 1: new UserResource($user)
            if (preg_match('/new\s+(\w+Resource)\s*\(/', $methodContent, $matches)) {
                $resourceClass = $this->findResourceClass($matches[1], $reflection->getNamespaceName());
                if ($resourceClass && is_subclass_of($resourceClass, JsonResource::class)) {
                    return $this->buildResponseSchemaFromResource($resourceClass);
                }
            }
            
            // Pattern 2: UserResource::class
            if (preg_match('/(\w+Resource)::class/', $methodContent, $matches)) {
                $resourceClass = $this->findResourceClass($matches[1], $reflection->getNamespaceName());
                if ($resourceClass && is_subclass_of($resourceClass, JsonResource::class)) {
                    return $this->buildResponseSchemaFromResource($resourceClass);
                }
            }
            
            // Pattern 3: Check use statements at the top of the file
            $fileContent = file_get_contents($reflection->getFileName());
            if (preg_match('/use\s+([^\s;]+Resource)/', $fileContent, $matches)) {
                $fullResourceClass = $matches[1];
                if (class_exists($fullResourceClass) && is_subclass_of($fullResourceClass, JsonResource::class)) {
                    // Check if it's used in the method
                    if (str_contains($methodContent, class_basename($fullResourceClass))) {
                        return $this->buildResponseSchemaFromResource($fullResourceClass);
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore reflection errors
        }

        return null;
    }

    protected function findResourceClass($className, $namespace)
    {
        $possibleNamespaces = [
            $namespace . '\\Http\\Resources',
            $namespace . '\\Http\\Resources\\V1',
            'App\\Http\\Resources',
            'Modules\\Channel\\App\\Http\\Resources',
        ];

        foreach ($possibleNamespaces as $ns) {
            $fullClass = $ns . '\\' . $className;
            if (class_exists($fullClass)) {
                return $fullClass;
            }
        }

        return null;
    }

    protected function buildResponseSchemaFromResource($resourceClass)
    {
        if (!class_exists($resourceClass)) {
            return null;
        }

        try {
            $reflection = new ReflectionClass($resourceClass);
            $toArrayMethod = $reflection->getMethod('toArray');
            
            // Create a mock request
            $mockRequest = new \Illuminate\Http\Request();
            
            // Try to instantiate resource with null (for schema generation)
            $resource = $reflection->newInstance((object)[]);
            
            // Get the structure from toArray method
            $properties = [];
            $example = [];
            
            // Parse the toArray method to extract structure
            $methodCode = file_get_contents($reflection->getFileName());
            $startLine = $toArrayMethod->getStartLine();
            $endLine = $toArrayMethod->getEndLine();
            $methodLines = array_slice(explode("\n", $methodCode), $startLine - 1, $endLine - $startLine + 1);
            $methodContent = implode("\n", $methodLines);

            // Extract return array structure
            if (preg_match('/return\s+\[(.*?)\];/s', $methodContent, $matches)) {
                $arrayContent = $matches[1];
                
                // Parse array items
                preg_match_all("/'([^']+)'\s*=>\s*\$this->(\w+)/", $arrayContent, $fieldMatches);
                
                if (!empty($fieldMatches[1])) {
                    foreach ($fieldMatches[1] as $index => $field) {
                        $property = $fieldMatches[2][$index] ?? $field;
                        $properties[$field] = $this->inferPropertyType($property);
                        $example[$field] = $this->getExampleValue($property);
                    }
                }
            }

            if (empty($properties)) {
                // Fallback: use common resource structure
                $properties = [
                    'id' => ['type' => 'integer', 'example' => 1],
                    'name' => ['type' => 'string', 'example' => 'Example'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time', 'example' => '2024-01-01 00:00:00'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time', 'example' => '2024-01-01 00:00:00'],
                ];
                $example = [
                    'id' => 1,
                    'name' => 'Example',
                    'created_at' => '2024-01-01 00:00:00',
                    'updated_at' => '2024-01-01 00:00:00',
                ];
            }

            return [
                'type' => 'object',
                'properties' => [
                    'status' => [
                        'type' => 'string',
                        'example' => 'success',
                    ],
                    'message' => [
                        'type' => 'string',
                        'example' => 'Operation successful',
                    ],
                    'data' => [
                        'type' => 'object',
                        'properties' => $properties,
                    ],
                ],
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function inferPropertyType($property)
    {
        $typeMap = [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
            'email' => ['type' => 'string', 'format' => 'email'],
            'phone' => ['type' => 'string'],
            'status' => ['type' => 'integer'],
            'is_active' => ['type' => 'boolean'],
            'created_at' => ['type' => 'string', 'format' => 'date-time'],
            'updated_at' => ['type' => 'string', 'format' => 'date-time'],
            'channel_id' => ['type' => 'integer'],
        ];

        return $typeMap[$property] ?? ['type' => 'string'];
    }

    protected function getExampleValue($property)
    {
        $valueMap = [
            'id' => 1,
            'name' => 'Example Name',
            'email' => 'example@email.com',
            'phone' => '+1234567890',
            'status' => 1,
            'is_active' => true,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
            'channel_id' => 1,
        ];

        return $valueMap[$property] ?? 'example';
    }

    protected function generateResponseExample($schema, $statusCode = 200)
    {
        if (!$schema || !isset($schema['properties']['data'])) {
            return [
                'status' => 'success',
                'message' => $statusCode === 201 ? 'Resource created successfully' : 'Operation successful',
                'data' => new \stdClass(),
            ];
        }

        $dataExample = [];
        if (isset($schema['properties']['data']['properties'])) {
            foreach ($schema['properties']['data']['properties'] as $field => $fieldSchema) {
                $dataExample[$field] = $fieldSchema['example'] ?? $this->getExampleValue($field);
            }
        }

        return [
            'status' => 'success',
            'message' => $statusCode === 201 ? 'Resource created successfully' : 'Operation successful',
            'data' => $dataExample,
        ];
    }
}

