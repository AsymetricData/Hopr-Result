<?php

use Hopr\Result\Result;

use function Hopr\Result\err;
use function Hopr\Result\ok;

require 'vendor/autoload.php';

// Just a simple ValueObject for the example
class User
{
    public function __construct(public string $name)
    {
    }
}

/**
* @return Result<User, string>
*/
function getUser(): Result
{
    return (bool) mt_rand(0, 1)
    ? ok(new User('Hopr'))
    : err('Err while connecting to db.')
    ;
}

/**
* @return Result<string[], string>
*/
function getPosts(): Result
{
    return (bool) mt_rand(0, 1)
    ? ok(['post1', 'post2'])
    : err('Err posts')
    ;
}

$user = getUser()
    ->bind(fn (User $user) => getPosts($user))
    // Just doing stuff on the returned array
    ->map(fn (array $arr) => array_map(fn ($a) => $a.$a, $arr))
    // Same stuff that doesn't make any sense
    ->map(fn (array $arr) => array_map(fn ($a) => $a.$a, $arr))
    ->mapErr(function ($err) {
        // Log the error somewhere...
        // and return a 500 for the user
        return 'Internal Server Error';
    })
;

var_dump($user);
