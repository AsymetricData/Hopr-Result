<?php

use function Hopr\Result\ok;

require 'vendor/autoload.php';

// Some functions to the following examples

function asTitle(string $label): string
{
    return sprintf('<h1>%s</h1>', $label);
}

function inBody(string $label): string
{
    return sprintf('<body>%s</body>', $label);
}

function inHtml(string $label): string
{
    return sprintf('<html>%s</html>', $label);
}

// Create an new Ok value using the helper function, you also can call Ok::of($val)
$ok = ok("Hopr")
    ->map(fn ($name) => asTitle($name))
    ->map(fn ($content) => inBody($content))
    ->map(fn ($content) => inHtml($content))
;

echo $ok->unwrapOr('Value if I was an err !');
