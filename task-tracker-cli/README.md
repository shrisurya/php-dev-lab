# Task Tracker CLI

Task Tracker is a small PHP command line application for managing tasks. It stores tasks in a local `tasks.json` file and uses only PHP's native filesystem functions.

## project URL

https://github.com/shrisurya/php-dev-lab/tree/master/task-tracker-cli

## Requirements

- PHP 8.1 or newer

## Usage

Run commands from this directory:

```bash
php task-cli.php add "Buy groceries"
php task-cli.php update 1 "Buy groceries and cook dinner"
php task-cli.php delete 1
php task-cli.php mark-in-progress 1
php task-cli.php mark-done 1
php task-cli.php list
php task-cli.php list done
php task-cli.php list todo
php task-cli.php list in-progress
```

On Windows, you can also run:

```bat
task-cli add "Buy groceries"
task-cli list
```

## Task Data

Tasks are saved to `tasks.json` in the current directory. The file is created automatically when it does not exist.

Each task contains:

- `id`
- `description`
- `status`
- `createdAt`
- `updatedAt`

Statuses can be `todo`, `in-progress`, or `done`.

## Error Handling

The CLI validates commands, IDs, descriptions, JSON parsing, and status filters. Errors are printed to standard error and return a non-zero exit code.
