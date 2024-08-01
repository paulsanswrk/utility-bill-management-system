<?php

use App\Services\UBMS_Security_Service;

test('test UBMS_Security_Service', function () {
    $service = new UBMS_Security_Service();

    $salt = $service->gen_salt_bytes();
    expect(strlen($salt))->toBe(16);


    //hash
    $hash = $service->hash('plaintext', $salt);
    expect(strlen($hash))->toBe(16 + 96);

    expect($service->hash_verify($hash, 'plaintext'))->toBeTrue();

    //cipher
    $key = $service->gen_cipher_key("qqq", $salt);
    expect(strlen($key))->toBe(32);

    $iv = $service->generate_iv();
    expect(strlen($iv))->toBe(16);

    $cipher_text = $service->encrypt('plaintext', $key, $iv);
    expect($service->decrypt($cipher_text, $key))->toBe('plaintext');

    $encrypted_key = $service->encrypt($key, $key, $iv);
    expect(strlen($encrypted_key))->toBe(16 + 32);

    echo "iv: " . bin2hex($iv) . "\n";

    $key = $service->gen_key_bytes($service->key_bit_len / 8);

    echo "key: " . bin2hex($key) . "\n";

    ob_flush();

});
