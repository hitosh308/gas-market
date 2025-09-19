<?php

declare(strict_types=1);

/**
 * Load application configuration.
 */
function getAppConfig(): array
{
    static $config;
    if ($config === null) {
        $config = require __DIR__ . '/../config/config.php';
    }

    return $config;
}

/**
 * Get configured path of the JSON data file.
 */
function getDataFilePath(): string
{
    $config = getAppConfig();

    return $config['data_file'];
}

/**
 * Return administrator password configured for the site.
 */
function getAdminPassword(): string
{
    $config = getAppConfig();

    return $config['admin_password'];
}

/**
 * Load GAS project definitions from the JSON file.
 *
 * @return array<int,array<string,mixed>>
 */
function loadGasProjects(): array
{
    $file = getDataFilePath();

    if (!file_exists($file)) {
        return [];
    }

    $json = file_get_contents($file);

    if ($json === false) {
        return [];
    }

    $data = json_decode($json, true);

    return is_array($data) ? $data : [];
}

/**
 * Persist GAS project definitions to the JSON file.
 */
function saveGasProjects(array $projects): bool
{
    $file = getDataFilePath();
    $json = json_encode($projects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    if ($json === false) {
        return false;
    }

    return (bool) file_put_contents($file, $json . PHP_EOL, LOCK_EX);
}

/**
 * Retrieve a GAS project by ID.
 */
function getGasProjectById(array $projects, string $id): ?array
{
    foreach ($projects as $project) {
        if (($project['id'] ?? '') === $id) {
            return $project;
        }
    }

    return null;
}

/**
 * Normalize the identifier used in URLs.
 */
function sanitizeProjectId(string $id): string
{
    $id = strtolower(trim($id));
    $id = preg_replace('/[^a-z0-9\-]+/', '-', $id) ?? '';
    $id = trim($id, '-');

    return $id !== '' ? $id : uniqid('gas-', false);
}

/**
 * Generate a new project identifier from a title.
 */
function generateProjectId(string $title): string
{
    $slug = strtolower(trim($title));
    $slug = preg_replace('/\s+/u', '-', $slug) ?? '';
    $slug = preg_replace('/[^a-z0-9\-]+/u', '-', $slug) ?? '';
    $slug = trim($slug, '-');

    if ($slug === '') {
        $slug = uniqid('gas-', false);
    }

    return $slug;
}

/**
 * Update an existing project entry within the list.
 */
function replaceProject(array $projects, array $project): array
{
    foreach ($projects as $index => $existing) {
        if (($existing['id'] ?? '') === ($project['id'] ?? '')) {
            $projects[$index] = $project;
            return $projects;
        }
    }

    $projects[] = $project;

    return $projects;
}
