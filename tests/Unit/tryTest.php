<?php

use Hopr\Result\Error;
use Hopr\Result\Ok;

use function Hopr\Result\tryTo;

it('Can use tryTo with something that doesnt throws', function () {
    $fn = function (): true {
        return true;
    };
    $val = tryTo($fn);

    expect($val)->toBeInstanceOf(Ok::class);
});

it('Can use tryTo with something that throws', function () {
    $fn = function (): bool {
        throw new \Exception('Im an error');
        return true;
    };
    $val = tryTo($fn);

    expect($val)->toBeInstanceOf(Error::class);
});
