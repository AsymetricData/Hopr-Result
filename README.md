# 🐇 Hopr\Result

A PHP Result type drawing from Gleam, delivering essential functionality in a concise, efficient package.

Provides fundamental outcome handling with minimal overhead.

I just wanted a Result library with an API I find *natural*.

## 🚀 Key features

### ✅ Fluent workflow with nice API
```php
$result = ok(5)
    ->map(fn($x) => $x * 2)        // Ok(10)
    ->map(fn($x) => $x + 1);       // Ok(11)
```

### 🧩 `use` Gleam keyword for PHP

Elegant destructuring and dependency injection:

```php
$cat_result = ok(['name' => 'Luna', 'age' => 7])
    // Bind `name` to the returned value of the callable
    ->use('name', fn($data) =>
        isset($data['name']) ? ok($data['name']) : err('Missing name'))
    // Bind `age` to the returned value of the callable. `name` is accessible here.
    ->use('age', fn($data, $name) =>
        isset($data['age']) ? ok($data['age']) : err('Missing age'))
    // And mapWith the previous `use` extracted fields!
    ->mapWith(fn($data, $name, $age) =>
        new Cat(name: $name, age: $age));
```

### ⚡️ Zero-dependency
Pure PHP with no external runtime dependencies. Lightweight, easy to read, easy to debug.

## 📦 Installation

```bash
composer require hopr/result
```

## Usage

See `examples/` for more examples.

### Create results

```php
use function Hopr\Result\{ok, err};

// You can also use Ok::of(42);
$success = ok(42);
// You can also use Error::of("...");
$failure = err("something went wrong");
```

### Chain transformations
```php
$result = ok(5)
    ->map(fn($x) => $x * 2)        // Ok(10)
    ->map(fn($x) => $x + 1);       // Ok(11)
```

#### Graceful error handling
```php
$result = err("bad input")
    ->map(fn($x) => $x * 2)           // Still err("bad input")
    ->mapErr(fn($e) => "Error: $e");  // err("Error: bad input")
```

### Safe unwrapping

```php
if ($result->isOk()) {
    $value = $result->unwrap();       // ✅ safe
} else {
    $value = $result->unwrapOr(0);    // fallback value
}
```

### Monadic binding

```php
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
