# Beetroot

This package provides tools to enhance Laravel commands with additional features, like validation for input arguments and options.

## Installation

Install the package via Composer:

```bash
composer require kobylinski/beetroot
```

## Input Validation

The WithValidate trait allows you to define validation rules for your command's input arguments and options. This ensures that the data passed to your command is clean and adheres to specific requirements.

### Usage
1. Add the WithValidate trait to your command.
2. Define a rules method in your command class to specify validation rules.

### Example
```php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Kobylinski\Beetroot\WithValidate;

class AddUserCommand extends Command
{
  use WithValidate;

  protected $signature = "add-user {handle}";

  protected $description = "Add a new user with a unique handle";

  /**
   * Define validation rules for the command input.
   *
   * @return array
   */
  protected function rules(): array
  {
    return [
      "handle" => [
        "string", // Ensure it's a string
        "max:255", // Limit length to 255 characters
        "unique:users,handle", // Ensure it's unique in the 'users' table
      ],
    ];
  }

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle(): int
  {
    $handle = $this->argument("handle");
    $this->info("User '{$handle}' added successfully!");

    return Command::SUCCESS;
  }
}

```

### Custom Validation Messages

You can define custom error messages for specific validation rules by adding a messages method to your command.

```php
/**
 * Define custom validation messages.
 *
 * @return array
 */
protected function messages(): array
{
  return [
    "handle.unique" => "A user with this handle already exists.",
  ];
}
```

## Nested Subcommands

This helper allows you to define subcommands directly in the command's $signature, simplifying the creation of complex CLI tools with hierarchical structures.

### Defining Subcommands

The helper introduces the ability to:
* Define subcommands within the $signature.
* Nest subcommands with their own arguments, options, and descriptions.
* Group commands logically for better usability.

### Example Signature

```php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Kobylinski\Beetroot\WithSubcommands;

class AddUserCommand extends Command
{
  use WithSubcommands;

  protected $signature = 'user 
    {UserCommand
      (add 
        {handle : The handle of the user}
        {name : The name of the token}
        {abilities?* : The abilities of the token})
      (*find
        {handle? : Part of the handle of the user})
      (suspend|restore
        {handle : The handle of the user})
      (remove
        {handle : The handle of the user}
        {name : The name of the token})
      (token
        {handle : The handle of the user}
        {TokenCommand
          (add 
            {name : The name of the token}
            {abilities?* : The abilities of the token})
          (remove
            {name : The name of the token})
        })
    } 
  ';

```

### How It Works
1. Top-Level Command: The main command (user in this example).
2. Subcommands: Nested command groups (e.g., add, find, suspend|restore).
3. Arguments and Options: Each subcommand can define its own arguments and options.
4. Multi-level Nesting: Subcommands can themselves have nested subcommands (e.g., token with its own subcommands).

### Handling Subcommands in handle

Your handle method processes the input, determines the subcommand invoked, and executes the corresponding logic.

```php
public function handle(): int
{
  return match ($this->argument("UserCommand")) {
    "add" => $this->addUser(),
    "find" => $this->findUser(),
    "suspend", "restore" => $this->toggleUserStatus(),
    "remove" => $this->removeUser(),
    "token" => $this->handleTokenSubcommand(),
    default => Command::INVALID,
  };
}
```

### Running the Command

```bash
# add user
php artisan user add johndoe read write

# find user
php artisan user find john

# suspend user
php artisan user suspend johndoe

# add new token
php artisan user token johndoe add monitoring read
```

### Benefits
* Readable Structure: Subcommands and their arguments are clearly defined in the signature.
* Extensible: Add new subcommands without restructuring your entire command.
* Logical Grouping: Keeps related functionality together.