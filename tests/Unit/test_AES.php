<?php

test('test hash_pbkdf2', function () {
    $plaintext = 'qqq';
    $key = 'www';

    // Generate an initialization vector (IV) for AES-256-CBC
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = openssl_random_pseudo_bytes($ivLength);


    // Encrypt the data
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

    var_dump($ciphertext);


    $plaintext = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

    echo "ivLength: $ivLength\n";
    var_dump($plaintext);

    ob_flush();
    expect(true)->toBeTrue();
});
