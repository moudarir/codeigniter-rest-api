<?php

namespace Moudarir\CodeigniterApi\Models\Users;

use Moudarir\CodeigniterApi\Models\TableFactory;
use Exception;

class Authentication extends TableFactory
{

    /**
     * @var array
     */
    protected array $messages = [];

    /**
     * @var array
     */
    protected array $errors = [];

    /**
     * Authentication constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param string $email
     * @param string $password
     * @return User
     * @throws Exception
     */
    public function login(string $email, string $password = ''): User
    {
        if (empty($email) || empty($password)) {
            throw new Exception("Erreur lors de la connexion.");
        }

        $user = (new User())->find(null, ['email' => $email, 'role' => true]);

        if ($user === null) {
            throw new Exception("Erreur lors de la connexion.");
        }

        if ($this->verifyPassword($password, $user->getPassword()) === false) {
            throw new Exception("Erreur lors de la connexion.");
        }

        if ($user->getActive() === 0) {
            throw new Exception("Votre compte est inactif pour le moment.");
        }

        $this->updateOnSuccessLogin($user);

        return $user;
    }

    /**
     * @param User $user
     */
    public function updateOnSuccessLogin(User $user): void
    {
        $user
            ->setForgottenPasswordSelector()
            ->setForgottenPasswordCode()
            ->setForgottenPasswordTime()
            ->setIpAddress($this->input->ip_address())
            ->setLastLogin(time());

        $user->update();
    }

    /**
     * This function takes a password and validates it
     * against an entry in the users table.
     *
     * @param string|null $password
     * @param string|null $hashDbPassword
     * @return bool
     */
    private function verifyPassword(?string $password = null, ?string $hashDbPassword = null): bool
    {
        // Check for empty id or password, or password containing null char, or password above limit
        // Null char may pose issue: http://php.net/manual/en/function.password-hash.php#118603
        // Long password may pose DOS issue (note: strlen gives size in bytes and not in multibyte symbol)
        if (empty($password) || empty($hashDbPassword) || strpos($password, "\0") !== false ||
            strlen($password) > 4096) {
            return false;
        }

        return password_verify($password, $hashDbPassword);
    }
}
