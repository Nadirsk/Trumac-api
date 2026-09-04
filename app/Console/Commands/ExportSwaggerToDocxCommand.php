<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\File;

class ExportSwaggerToDocxCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swagger:export-docx {--output= : Output file path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export Swagger API documentation to DOCX format';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Exporting Swagger API documentation to DOCX format...');
        
        // Get the Swagger JSON file
        $swaggerPath = storage_path('api-docs/api-docs.json');
        
        if (!File::exists($swaggerPath)) {
            $this->error("Swagger JSON file not found at: {$swaggerPath}");
            return 1;
        }
        
        $swaggerJson = File::get($swaggerPath);
        $swaggerData = json_decode($swaggerJson, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Error parsing Swagger JSON: ' . json_last_error_msg());
            return 1;
        }
        
        // Create a new PHPWord object
        $phpWord = new PhpWord();
        
        // Add document properties
        $properties = $phpWord->getDocInfo();
        $properties->setTitle('DR TODAY API Documentation');
        $properties->setCreator('DR TODAY API');
        $properties->setDescription('API Documentation for DR TODAY application');
        
        // Add title
        $section = $phpWord->addSection();
        $section->addTitle('DR TODAY API Documentation', 1);
        
        // Add API info
        if (isset($swaggerData['info'])) {
            $info = $swaggerData['info'];
            
            $section->addTitle('API Information', 2);
            $section->addText("Title: " . (isset($info['title']) ? $info['title'] : 'N/A'));
            $section->addText("Version: " . (isset($info['version']) ? $info['version'] : 'N/A'));
            $section->addText("Description: " . (isset($info['description']) ? $info['description'] : 'N/A'));
            
            if (isset($info['contact'])) {
                $section->addTextBreak();
                $section->addText("Contact: " . (isset($info['contact']['email']) ? $info['contact']['email'] : 'N/A'));
            }
        }
        
        $section->addTextBreak(2);
        
        // Add paths
        if (isset($swaggerData['paths']) && is_array($swaggerData['paths'])) {
            $section->addTitle('API Endpoints', 2);
            
            foreach ($swaggerData['paths'] as $path => $pathData) {
                foreach ($pathData as $method => $operation) {
                    $method = strtoupper($method);
                    
                    // Add path and method
                    $section->addTitle("{$method} {$path}", 3);
                    
                    // Add summary if available
                    if (isset($operation['summary'])) {
                        $section->addText("Summary: " . $operation['summary'], ['bold' => true]);
                    }
                    
                    // Add description if available
                    if (isset($operation['description'])) {
                        $section->addText("Description: " . $operation['description']);
                    }
                    
                    // Add tags
                    if (isset($operation['tags']) && is_array($operation['tags'])) {
                        $section->addText("Tags: " . implode(', ', $operation['tags']), ['italic' => true]);
                    }
                    
                    // Add parameters
                    if (isset($operation['parameters']) && is_array($operation['parameters'])) {
                        $section->addText("Parameters:", ['bold' => true]);
                        
                        foreach ($operation['parameters'] as $param) {
                            $paramName = isset($param['name']) ? $param['name'] : 'N/A';
                            $paramIn = isset($param['in']) ? $param['in'] : 'N/A';
                            $paramDesc = isset($param['description']) ? $param['description'] : 'N/A';
                            
                            $section->addListItem("{$paramName} ({$paramIn}): {$paramDesc}");
                        }
                    }
                    
                    // Add responses
                    if (isset($operation['responses']) && is_array($operation['responses'])) {
                        $section->addText("Responses:", ['bold' => true]);
                        
                        foreach ($operation['responses'] as $responseCode => $response) {
                            $responseDesc = isset($response['description']) ? $response['description'] : 'N/A';
                            $section->addListItem("{$responseCode}: {$responseDesc}");
                        }
                    }
                    
                    $section->addTextBreak();
                }
            }
        }
        
        // Generate output filename
        $outputPath = $this->option('output');
        if (!$outputPath) {
            $outputPath = public_path('docs/api-documentation.docx');
            
            // Create docs directory if it doesn't exist
            $docsDir = dirname($outputPath);
            if (!File::exists($docsDir)) {
                File::makeDirectory($docsDir, 0755, true);
            }
        }
        
        // Save the document
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($outputPath);
        
        $this->info("API documentation exported successfully to: {$outputPath}");
        
        return 0;
    }
}
