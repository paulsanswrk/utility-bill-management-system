<?php

test('test hash_pbkdf2', function () {
    $password = "password";
    $iterations = 300000;

// Generate a cryptographically secure random salt using random_bytes()
    $salt = openssl_random_pseudo_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);

    echo bin2hex($salt);


    $hash = hash_pbkdf2("sha256", $password, $salt, $iterations, 200, false);
//    var_dump($hash);
    ob_flush();
    expect(true)->toBeTrue();
});
