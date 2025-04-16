# 🐇 Hopr\Result

A PHP Result type drawing from Gleam, delivering essential functionality in a concise, efficient package.

Provides fundamental outcome handling with minimal overhead.

I just wanted a Result library with an API I find *natural*.

## Installation

```bash
composer require hopr/result
```

## Usage

See `examples/` for more examples.

```php
use function Hopr\Result\{ok, err};

// Create results
$success = ok(42);
$failure = err("something went wrong");

// Chain operations
$result = ok(5)
    ->map(fn($x) => $x * 2)        // Ok(10)
    ->map(fn($x) => $x + 1);       // Ok(11)

// Handle errors gracefully
$result = err("bad input")
    ->map(fn($x) => $x * 2)        // Error("bad input")
    ->mapErr(fn($e) => "Error: $e"); // Error("Error: bad input")

// Safe unwrapping
if ($result->isOk()) {
    $value = $result->unwrap();     // Get value safely
} else {
    $value = $result->unwrapOr(0);  // Use default value
}

// Monadic binding
$parseNumber = function(string $input) {
    return is_numeric($input) 
        ? ok((int)$input)
        : err("Not a number");
};

$result = ok("42")->bind($parseNumber);  // Ok(42)
```

For more examples, check the interface documentation in `src/Result.php`.

# The Hopr project

A set of PHP components exploring novel features and alternative design patterns that could enrich the language's standard library.
