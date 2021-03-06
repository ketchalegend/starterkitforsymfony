<?php

namespace AppBundle\Service\Credential;

use AppBundle\Entity\User;
use AppBundle\Exception\ProgrammerException;
use AppBundle\Model\Security\AuthTokenModel;
use Namshi\JOSE\SimpleJWS;

/**
 * Class JWSService
 * @package AppBundle\Service
 */
class JWSService
{
    const ALG = 'RS256';

    /**
     * This is the key in the array of the payload to represent expiration time for jws token
     * @var string
     */
    const EXP_KEY = 'exp';

    /**
     * This is the key in the array of the payload to represent time the jwt token was issued time for jws token
     * @var string
     */
    const IAT_KEY = 'iat';

    /**
     * This is the key in the array of the payload to represent time the user id that the token was issued for
     * @var string
     */
    const USER_ID_KEY = 'user_id';

    /**
     * The number of seconds before the token expires
     * @var integer
     */
    private $ttl;

    /**
     * The pass phrase used create tokens
     * @var string
     */
    private $passPhrase;

    /**
     * JWSService constructor.
     * @param $passPhrase
     * @param $ttl
     */
    public function __construct($passPhrase, $ttl)
    {
        $this->ttl = $ttl;
        $this->passPhrase = $passPhrase;
    }

    /**
     * Creates a jws token model
     *
     * @param User $user
     * @return AuthTokenModel
     */
    public function createAuthTokenModel(User $user)
    {
        $privateKey = openssl_pkey_get_private(file_get_contents(__DIR__ . '/../../../../var/jwt/private.pem'), $this->passPhrase);

        $jws = new SimpleJWS([
            'alg' => self::ALG
        ]);

        $expirationDate = new \DateTime();
        $expirationDate->modify('+' . $this->ttl . ' seconds');
        $expirationTimestamp = $expirationDate->getTimestamp();

        $jws->setPayload([
            self::USER_ID_KEY => $user->getId(),
            self::EXP_KEY => $expirationTimestamp,
            self::IAT_KEY => (new \DateTime())->getTimestamp()
        ]);

        $jws->sign($privateKey);

        return new AuthTokenModel($jws->getTokenString(), $expirationTimestamp);
    }

    /**
     * Returns true if the token is valid
     *
     * @param string $token
     * @return bool
     */
    public function isValid($token)
    {
        try {
            $publicKey = openssl_pkey_get_public(file_get_contents(__DIR__ . '/../../../../var/jwt/public.pem'));

            $jws = SimpleJWS::load($token);

            return $jws->verify($publicKey, self::ALG) && $this->isTokenNotExpired($token);
        } catch (\InvalidArgumentException $ex) {
            return false;
        }
    }

    /**
     * Gets the payload out of the token.
     *
     * @param $token
     * @return array
     * @throws ProgrammerException
     */
    public function getPayload($token)
    {
        try {
            $jws = SimpleJWS::load($token);

            return $jws->getPayload();
        } catch (\InvalidArgumentException $ex) {
            throw new ProgrammerException('Unable to read jws token.', ProgrammerException::JWS_INVALID_TOKEN_FORMAT);
        }

    }

    private function isTokenNotExpired($token)
    {
        $payload = $this->getPayload($token);

        return isset($payload[self::EXP_KEY]) && $payload[self::EXP_KEY] > (new \DateTime())->getTimestamp();
    }
}