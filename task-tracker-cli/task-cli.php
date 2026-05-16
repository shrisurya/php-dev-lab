<?php

declare(strict_types=1);

const TASKS_FILE = 'tasks.json';
const VALID_STATUSES = ['todo', 'in-progress', 'done'];

main($argv);

function main(array $argv): void
{
    $command = $argv[1] ?? null;
    $args = array_slice($argv, 2);

    try {
        $tasks = loadTasks();

        switch ($command) {
            case 'add':
                addTask($tasks, $args);
                break;

            case 'update':
                updateTask($tasks, $args);
                break;

            case 'delete':
                deleteTask($tasks, $args);
                break;

            case 'mark-in-progress':
                markTask($tasks, $args, 'in-progress');
                break;

            case 'mark-done':
                markTask($tasks, $args, 'done');
                break;

            case 'list':
                listTasks($tasks, $args);
                break;

            case 'help':
            case '--help':
            case '-h':
            case null:
                printUsage();
                break;

            default:
                fail("Unknown command: {$command}");
        }
    } catch (RuntimeException $exception) {
        fwrite(STDERR, 'Error: ' . $exception->getMessage() . PHP_EOL);
        exit(1);
    }
}

function loadTasks(): array
{
    if (!file_exists(TASKS_FILE)) {
        saveTasks([]);
        return [];
    }

    $json = file_get_contents(TASKS_FILE);

    if ($json === false) {
        fail('Unable to read ' . TASKS_FILE . '.');
    }

    if (trim($json) === '') {
        return [];
    }

    $tasks = json_decode($json, true);

    if (!is_array($tasks)) {
        fail(TASKS_FILE . ' contains invalid JSON.');
    }

    return $tasks;
}

function saveTasks(array $tasks): void
{
    $json = json_encode(array_values($tasks), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if ($json === false) {
        fail('Unable to encode tasks as JSON.');
    }

    if (file_put_contents(TASKS_FILE, $json . PHP_EOL, LOCK_EX) === false) {
        fail('Unable to write ' . TASKS_FILE . '.');
    }
}

function addTask(array $tasks, array $args): void
{
    $description = trim($args[0] ?? '');

    if ($description === '') {
        fail('Task description is required. Usage: php task-cli.php add "Buy groceries"');
    }

    $now = currentTimestamp();
    $id = nextTaskId($tasks);

    $tasks[] = [
        'id' => $id,
        'description' => $description,
        'status' => 'todo',
        'createdAt' => $now,
        'updatedAt' => $now,
    ];

    saveTasks($tasks);
    echo "Task added successfully (ID: {$id})" . PHP_EOL;
}

function updateTask(array $tasks, array $args): void
{
    [$id, $description] = parseIdAndDescription($args, 'update');
    $index = findTaskIndex($tasks, $id);

    $tasks[$index]['description'] = $description;
    $tasks[$index]['updatedAt'] = currentTimestamp();

    saveTasks($tasks);
    echo "Task updated successfully (ID: {$id})" . PHP_EOL;
}

function deleteTask(array $tasks, array $args): void
{
    $id = parseId($args[0] ?? null, 'delete');
    $index = findTaskIndex($tasks, $id);

    array_splice($tasks, $index, 1);

    saveTasks($tasks);
    echo "Task deleted successfully (ID: {$id})" . PHP_EOL;
}

function markTask(array $tasks, array $args, string $status): void
{
    $id = parseId($args[0] ?? null, "mark task as {$status}");
    $index = findTaskIndex($tasks, $id);

    $tasks[$index]['status'] = $status;
    $tasks[$index]['updatedAt'] = currentTimestamp();

    saveTasks($tasks);
    echo "Task marked as {$status} (ID: {$id})" . PHP_EOL;
}

function listTasks(array $tasks, array $args): void
{
    $status = $args[0] ?? null;

    if ($status !== null && !in_array($status, VALID_STATUSES, true)) {
        fail('Invalid status. Use one of: todo, in-progress, done.');
    }

    $filteredTasks = $status === null
        ? $tasks
        : array_values(array_filter($tasks, fn (array $task): bool => ($task['status'] ?? '') === $status));

    if ($filteredTasks === []) {
        echo 'No tasks found.' . PHP_EOL;
        return;
    }

    foreach ($filteredTasks as $task) {
        $id = $task['id'] ?? '?';
        $description = $task['description'] ?? '';
        $taskStatus = $task['status'] ?? 'unknown';
        $createdAt = $task['createdAt'] ?? 'unknown';
        $updatedAt = $task['updatedAt'] ?? 'unknown';

        echo "[{$id}] {$description}" . PHP_EOL;
        echo "    Status: {$taskStatus}" . PHP_EOL;
        echo "    Created: {$createdAt}" . PHP_EOL;
        echo "    Updated: {$updatedAt}" . PHP_EOL;
    }
}

function nextTaskId(array $tasks): int
{
    $highestId = 0;

    foreach ($tasks as $task) {
        $id = $task['id'] ?? 0;

        if (is_int($id) && $id > $highestId) {
            $highestId = $id;
        }
    }

    return $highestId + 1;
}

function findTaskIndex(array $tasks, int $id): int
{
    foreach ($tasks as $index => $task) {
        if (($task['id'] ?? null) === $id) {
            return $index;
        }
    }

    fail("Task with ID {$id} was not found.");
}

function parseIdAndDescription(array $args, string $command): array
{
    $id = parseId($args[0] ?? null, $command);
    $description = trim($args[1] ?? '');

    if ($description === '') {
        fail("Task description is required. Usage: php task-cli.php {$command} {$id} \"New description\"");
    }

    return [$id, $description];
}

function parseId(?string $value, string $command): int
{
    if ($value === null || !ctype_digit($value) || (int) $value < 1) {
        fail("A positive task ID is required for {$command}.");
    }

    return (int) $value;
}

function currentTimestamp(): string
{
    return date(DateTimeInterface::ATOM);
}

function printUsage(): void
{
    echo <<<TEXT
Task Tracker CLI

Usage:
  php task-cli.php add "Task description"
  php task-cli.php update <id> "Updated description"
  php task-cli.php delete <id>
  php task-cli.php mark-in-progress <id>
  php task-cli.php mark-done <id>
  php task-cli.php list [todo|in-progress|done]

Tasks are stored in tasks.json in the current directory.

TEXT;
}

function fail(string $message): never
{
    throw new RuntimeException($message);
}
