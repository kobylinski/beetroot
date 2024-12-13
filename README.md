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

## WithNamedParameters Trait

The `WithNamedParameters` trait adds the ability to define validation rules with named parameters in your Laravel application. This feature simplifies rule customization and parameter management in complex validation scenarios.

### Key Features

- **Named Parameters in Validation Rules**: Define parameters for custom validation rules directly in the validation string.
- **Automatic Parameter Mapping**: Maps the provided parameters to the rule attributes with default values.
- **Customizable Messages**: Provides a way to dynamically replace placeholders in error messages.
- **Registration of Custom Rules**: Automatically registers the custom rule with Laravel's validator.

### Example Usage

#### Validation Rule Definition

Define your validation rules with named parameters:

```php
[
  "field" => "required|my_rule:value1,strict,true,1.2.3"
]
```

#### Parameter Configuration Overview

In this example, the rule string `my_rule:value1,strict,true,1.2.3` maps directly to the following attributes:

- **`Value('category')`**: Maps to the `value1` parameter.
- **`Value('mode', dictionary: ['strict', 'lenient'])`**: Maps to the `strict` parameter, with validation restricted to the listed dictionary values.
- **`Flag('active')`**: Maps to the `true` parameter (interpreted as a boolean flag).
- **`Sequence('ids_to_exclude')`**: Maps to the `1.2.3` parameter (interpreted as a sequence of values).

These parameters are automatically accessible within the rule as class properties:

- `$this->category`
- `$this->mode`
- `$this->active`
- `$this->ids_to_exclude`

#### Abstract Example of a Custom Rule

Here’s a generalized implementation of a custom rule:

```php
<?php

use Kobylinski\Beetroot\Attributes\Value;
use Kobylinski\Beetroot\Attributes\Flag;
use Kobylinski\Beetroot\Attributes\Sequence;
use Kobylinski\Beetroot\Attributes\Rule;

#[Rule("my_rule")]
class MyCustomRule
{
    use WithNamedParameters;

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  Closure(string, ?string=): void  $fail
     */
    #[Value("category"), Value("mode", dictionary: ["strict", "lenient"]), Flag("active"), Sequence("ids_to_exclude")]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Example: Ensure the value matches the configured category
        if ($this->category !== $value) {
            $fail("The $attribute must belong to the {$this->category} category.");
        }

        // Additional checks based on mode
        if ($this->mode === 'strict' && strlen($value) < 5) {
            $fail("The $attribute must be at least 5 characters long in strict mode.");
        }

        // Example usage of flags and sequences
        if ($this->active && in_array($value, $this->ids_to_exclude)) {
            $fail("The $attribute cannot be one of the excluded values.");
        }
    }
}
```

### Attributes

#### `NamedParameter`
Define attributes for your custom validation parameters. Each `NamedParameter` can include:
- **`name`**: The name of the parameter.
- **`default`**: The default value for the parameter.
- **`adjust`**: (Optional) A callback to adjust or transform the parameter value.

#### `Value`
Defines a single value parameter with its name and an optional default value. You can specify a `dictionary` of allowed values to restrict input to predefined options.

#### `Flag`
Defines a boolean flag that can be included in the rule definition to toggle behavior.

#### `Sequence`
Defines a list of expected values that can be validated as part of the rule.

#### `Rule`
Associates a name with the validation rule for registration.

### Example Validator Configuration

Here’s an example of how to use a custom rule in your validation:

```php
use Illuminate\Support\Facades\Validator;

$data = [
    'field' => 'example_value',
];

$rules = [
    'field' => 'required|my_rule:value1,strict,true,1.2.3',
];

$validator = Validator::make($data, $rules);

if ($validator->fails()) {
    dd($validator->errors());
}
```

### Adding New Rules

To add a new rule with named parameters:

1. Create the rule using the Artisan command:

    ```bash
    php artisan make:rule MyCustomRule
    ```

2. Open the newly created rule file in `app/Rules/MyCustomRule.php`.
3. Use the `WithNamedParameters` trait.
4. Annotate the `validate` method with `NamedParameter` attributes.

    Example:

    ```php
    #[Value("category"), Value("mode", dictionary: ["strict", "lenient"]), Flag("active"), Sequence("ids_to_exclude")]
    ```

5. Implement your validation logic in the `validate` method.
6. Call the `register()` method to register the rule.

### Registering Custom Rules

You can register your rule in a service provider:

```php
use App\Rules\MyCustomRule;

public function boot()
{
    MyCustomRule::register();
}
```
