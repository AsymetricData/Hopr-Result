<?php

use function Hopr\Result\err;
use function Hopr\Result\ok;

test('use transmet bien les champs indiqués', function () {
    $cat_result = ok(['name' => 'Luna', 'age' => 7])
        // Bind `name` to the returned value of the callable
        ->use('name', fn($data) => isset($data['name']) ? ok($data['name']) : err('Missing name'))
        // Bind `age` to the returned value of the callable. `name` is accessible here.
        ->use('age', fn($data, $name) => isset($data['age']) ? ok($data['age']) : err('Missing age'))
        // And mapWith the previous `use` extracted fields !
        ->mapWith(fn($data, $name, $age) => [
            $name,
            $age,
        ]);

    expect($cat_result->unwrapOr(null))->toBe(['Luna', 7]);
});
