<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakePluginModel extends Command
{
	protected $signature = 'make:plugin-model
	{name : The name of the model}
	{plugin : The plugin name (e.g., ecommerce)}
	{--m : Create a migration}
	{--c : Create a controller}
	{--r : Create a resource controller}';

	protected $description = 'Create a model, migration, and controller inside a specific plugin';

	public function handle()
	{
		$modelName = $this->argument('name');
		$plugin = $this->argument('plugin');
		$createMigration = $this->option('m');
		$createController = $this->option('c') || $this->option('r');
		$isResource = $this->option('r');

		$pluginPath = base_path("platform/plugins/{$plugin}");

		// Ensure the plugin path exists
		if (!File::exists($pluginPath)) {
			$this->error("Plugin '{$plugin}' does not exist.");
			return 1;
		}

		// Create Model
		$modelNamespace = "Platform\\Plugins\\{$plugin}\\Models";
		$modelPath = "{$pluginPath}/src/Models/{$modelName}.php";

		// Generate model file manually
		File::ensureDirectoryExists("{$pluginPath}/src/Models");
		$modelContent = "<?php

namespace {$modelNamespace};

use Illuminate\Database\Eloquent\Model;

class {$modelName} extends Model
{
	// Model configuration can go here
}";

		File::put($modelPath, $modelContent);
		$this->info("Model created at: {$modelPath}");

		// Create Migration
		if ($createMigration) {
			$this->call('make:migration', [
				'name' => 'create_' . Str::snake(Str::pluralStudly($modelName)) . '_table',
			]);

			// Move the migration to the plugin directory
			$migrationFiles = File::glob(database_path('migrations/*create_' . Str::snake(Str::pluralStudly($modelName)) . '_table.php'));
			if ($migrationFiles) {
				$migrationFile = end($migrationFiles);
				$targetPath = "{$pluginPath}/database/migrations/" . basename($migrationFile);
				File::move($migrationFile, $targetPath);
				$this->info("Migration moved to: {$targetPath}");
			}
		}

		// Create Resource Controller
		if ($createController) {
			$controllerNamespace = "Botble\\Ecommerce\\Http\\Controllers;";
			$controllerPath = "{$pluginPath}/src/Http/Controllers/{$modelName}Controller.php";

			// Ensure directory exists
			File::ensureDirectoryExists("{$pluginPath}/src/Http/Controllers");

			// Resource controller content
			$controllerContent = "<?php

namespace {$controllerNamespace};

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class {$modelName}Controller extends BaseController
{
	/**
	 * Display a listing of the resource.
	 */
	public function index()
	{
		// Code for listing resources
	}

	/**
	 * Show the form for creating a new resource.
	 */
	public function create()
	{
		// Code for showing create form
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request \$request)
	{
		// Code for storing a resource
	}

	/**
	 * Display the specified resource.
	 */
	public function show(\$id)
	{
		// Code for showing a single resource
	}

	/**
	 * Show the form for editing the specified resource.
	 */
	public function edit(\$id)
	{
		// Code for showing edit form
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request \$request, \$id)
	{
		// Code for updating a resource
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(\$id)
	{
		// Code for deleting a resource
	}
}";

			// Write the controller content to the file
			File::put($controllerPath, $controllerContent);

			$this->info("Resource controller created at: {$controllerPath}");
		}


		$this->info('Plugin files generated successfully!');
		return 0;
	}
}
