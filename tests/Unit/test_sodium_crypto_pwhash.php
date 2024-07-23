<?php

test('test hash_pbkdf2', function () {
    $salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);

    echo bin2hex(
        sodium_crypto_pwhash(
            32, // == 256 bits
            'password',
            $salt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
        )
    );

    ob_flush();
    expect(true)->toBeTrue();
});
