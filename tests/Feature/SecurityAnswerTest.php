<?php

use App\Services\UBMS_Security_Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

/*spl_autoload_register(function ($class) {
    include '\app\\Services\\' . $class . 'php';
});*/

uses(RefreshDatabase::class);

it('allows a user to register with a security answer', function () {
    app()->instance(UBMS_Security_Service::class, new class extends UBMS_Security_Service {
        public function gen_keys_4_new_user(string $password, string $security_question, string $security_answer): array
        {
            return [
                'security_answer_hash' => '$security_answer_hash',
                'key_salt' => '$key_salt',
                'work_key_ciphered' => '$work_key_ciphered',
                'pwd_key_ciphered' => '$pwd_key_ciphered',

                //for session
                'session_key' => '$session_key',
                'pwd_ciphered' => '$pwd_ciphered',
                'session_work_key_ciphered' => '$session_work_key_ciphered',
            ];

        }

    });

    // Simulate a registration request
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'security_question' => 'What is your favorite color?',
        'security_answer' => 'Blue',
    ]);

    // Assert the response is a redirect
//    $response->assertRedirect('/home');

    // Check if the user is in the database with the correct security answer
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'key_salt' => bin2hex('$key_salt'),
        'work_key_encrypted' => bin2hex('$work_key_ciphered'),
        'pwd_key_encrypted' => bin2hex('$pwd_key_ciphered'),
        'security_question' => 'What is your favorite color?',
    ]);

    // Check if the security answer is in the session (assuming you store it in session)
    $response->assertSessionHas('session_key', bin2hex('$session_key'));
    $response->assertSessionHas('pwd_ciphered', bin2hex('$pwd_ciphered'));
    $response->assertSessionHas('work_key_ciphered', bin2hex('$session_work_key_ciphered'));
});
