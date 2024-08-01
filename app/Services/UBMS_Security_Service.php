<?php

namespace App\Services;

class UBMS_Security_Service
{

    public $key_bit_len = 256; //32 bytes
    private $cipher_algo = 'aes-256-cfb';
    private $master_key;
    private $master_iv;
    private int $iv_len; //16 bytes

    function __construct()
    {
        $this->iv_len = openssl_cipher_iv_length($this->cipher_algo);
        $this->master_key = hex2bin('ee87388b604cd15acb9910d3c888130f6b5c82ee0c9b4ab418681438b86313e0');
        $this->master_iv = hex2bin('83b34e507160128c02f449105db0aed0');
    }

    public function gen_salt_bytes(): string
    {
        return openssl_random_pseudo_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
    }

    public function gen_key_bytes(int $len = 0): string
    {
        $len = $len ?: $this->iv_len;
        return openssl_random_pseudo_bytes($len);
    }

    public function gen_cipher_key(string $seed, string $salt): string
    {
        return sodium_crypto_pwhash(
            $this->key_bit_len / 8,
            $seed,
            $salt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
        );
    }

    //password_hash() returns the algorithm, cost and salt as part of the returned hash https://www.php.net/manual/en/function.password-verify.php

    /**returns salt (16 bytes) + hash (96 bytes) = 112 bytes
     * @param string $plaintext
     * @param string $salt
     * @return string
     */
    public function hash(string $plaintext, string $salt): string
    {
        return $salt . password_hash($salt . $plaintext, PASSWORD_ARGON2I);
    }

    public function hash_verify(string $hashed_text, string $plaintext): bool
    {
        $salt = substr($hashed_text, 0, $this->iv_len);
        $hashed_text = substr($hashed_text, $this->iv_len);
        return password_verify($salt . $plaintext, $hashed_text);
    }

    public function generate_iv(): string
    {
        return openssl_random_pseudo_bytes($this->iv_len);
    }

    /** returns IV + cipher text
     * @param string $plaintext
     * @param string $key
     * @param string $iv
     * @return string IV + cipher text
     */
    public function encrypt(string $plaintext, string $key, string $iv = null): string
    {
        $iv ??= $this->generate_iv();
        return $iv . openssl_encrypt($plaintext, $this->cipher_algo, $key, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * @param string $ciphertext = IV + cipher text
     * @param string $key
     * @return string
     */
    public function decrypt(string $ciphertext, string $key): string
    {
        $iv = substr($ciphertext, 0, $this->iv_len);
        $ciphertext = substr($ciphertext, $this->iv_len);
        return openssl_decrypt($ciphertext, $this->cipher_algo, $key, OPENSSL_RAW_DATA, $iv);
    }

    public function create_sec_qa_key(string $sec_q, string $sec_a, string $key_salt): string
    {
        $sec_q = strtolower(trim($sec_q));
        $sec_a = strtolower(trim($sec_a));
        return $this->gen_cipher_key("$sec_q|$sec_a", $key_salt);
    }

    public function gen_keys_4_new_user(string $password): array
    {
        $work_key_plain = $this->gen_key_bytes();
        $work_key_encrypted = $this->encrypt($work_key_plain, $this->master_key);

        return [
            'work_key_encrypted' => $work_key_encrypted,
        ];
    }

    public function encrypt_with_user_key(string $plaintext, string $work_key_encrypted_hex): string
    {
        $work_key = $this->decrypt(hex2bin($work_key_encrypted_hex), $this->master_key);
        return $this->encrypt($plaintext, $work_key);
    }

    public function encrypt_file_with_user_key(string $fn, string $work_key_encrypted_hex)
    {
        $bytes = file_get_contents($fn);
        $work_key = $this->decrypt(hex2bin($work_key_encrypted_hex), $this->master_key);
        $bytes = $this->encrypt($bytes, $work_key);
        file_put_contents($fn, $bytes);
    }

    public function decrypt_with_user_key(string $ciphertext, string $work_key_encrypted_hex): string
    {
        $work_key = $this->decrypt(hex2bin($work_key_encrypted_hex), $this->master_key);
        return $this->decrypt($ciphertext, $work_key);
    }

    public function decrypt_file_with_user_key(string $fn, string $work_key_encrypted_hex): string
    {
        $bytes = file_get_contents($fn);
        $work_key = $this->decrypt(hex2bin($work_key_encrypted_hex), $this->master_key);
        return $this->decrypt($bytes, $work_key);
    }
}


