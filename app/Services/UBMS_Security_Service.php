<?php

namespace App\Services;

class UBMS_Security_Service
{

    public $key_bit_len = 256; //32 bytes
    private $cipher_algo = 'aes-256-cfb';
    private int $iv_len; //16 bytes

    function __construct()
    {
        $this->iv_len = openssl_cipher_iv_length($this->cipher_algo);
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

    public function gen_keys_4_new_user(string $password, string $security_question, string $security_answer): array
    {
        $security_answer_hash = $this->hash($security_answer, $this->gen_salt_bytes());
        $session_key = $this->gen_key_bytes();
        $pwd_ciphered = $this->encrypt($password, $session_key);
        $work_key_plain = $this->gen_key_bytes();
        $key_salt = $this->gen_salt_bytes();
        $pwd_key_plain = $this->gen_cipher_key($password, $key_salt);
        $sec_qa_key_plain = $this->create_sec_qa_key($security_question, $security_answer, $key_salt);
        $work_key_ciphered = $this->encrypt($work_key_plain, $pwd_key_plain);
        $session_work_key_ciphered = $this->encrypt($work_key_plain, $session_key);
        $pwd_key_ciphered = $this->encrypt($pwd_key_plain, $sec_qa_key_plain);

        return [
            'security_answer_hash' => $security_answer_hash,
            'key_salt' => $key_salt,
            'work_key_ciphered' => $work_key_ciphered,
            'pwd_key_ciphered' => $pwd_key_ciphered,

            //for session
            'session_key' => $session_key,
            'pwd_ciphered' => $pwd_ciphered,
            'session_work_key_ciphered' => $session_work_key_ciphered,
        ];
    }
}


