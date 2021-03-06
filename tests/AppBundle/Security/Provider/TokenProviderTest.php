<?php


namespace Test\AppBundle\Security\Provider;

use AppBundle\Entity\User;
use AppBundle\Repository\UserRepository;
use AppBundle\Security\Provider\TokenProvider;
use AppBundle\Service\Credential\JWSService;
use AppBundle\Service\User\UserService;
use Mockery\Mock;
use PHPUnit\Framework\Assert;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Tests\BaseTestCase;

class TokenProviderTest extends BaseTestCase
{
    /**
     * @var TokenProvider|Mock
     */
    protected $tokenProvider;

    /**
     * @var JWSService|Mock
     */
    protected $jwsService;

    /**
     * @var UserService|Mock
     */
    protected $userService;

    public function setUp()
    {
        parent::setUp();

        $this->jwsService = \Mockery::mock(JWSService::class);
        $this->userService = \Mockery::mock(UserService::class);
        $this->tokenProvider = new TokenProvider($this->userService, $this->jwsService);
    }

    /**
     * Tests happy path a valid token with a user id that is valid returns the User object
     */
    public function testValidToken()
    {
        $user = new User();
        $this->jwsService->shouldReceive('isValid')->with('token')->andReturn(true);
        $this->jwsService->shouldReceive('getPayload')->with('token')->andReturn(['user_id' => 33]);
        $this->userService->shouldReceive('findUserById')->with('33')->andReturn($user);
        $userFound = $this->tokenProvider->loadUserByUsername('token');

        Assert::assertEquals($user, $userFound);
    }

    /**
     * Tests that an invalid throws the UsernameNotFoundException
     */
    public function testInvalidToken()
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->jwsService->shouldReceive('isValid')->with('token')->andReturn(false);

        $this->tokenProvider->loadUserByUsername('token');
    }

    /**
     * Tests a token without user_id in payload throw UsernameNotFoundException
     */
    public function testTokenWithNoUserIdInPayload()
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->jwsService->shouldReceive('isValid')->with('token')->andReturn(true);
        $this->jwsService->shouldReceive('getPayload')->with('token')->andReturn(['id' => 33]);

        $this->tokenProvider->loadUserByUsername('token');
    }

    /**
     * Tests that if the user id is not found in the db it throws UsernameNotFoundException
     */
    public function testUserIdNotFoundInDatabase()
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->jwsService->shouldReceive('isValid')->with('token')->andReturn(true);
        $this->jwsService->shouldReceive('getPayload')->with('token')->andReturn(['user_id' => 33]);

        $this->userService->shouldReceive('findUserById')->with('33')->andReturnNull();
        $this->tokenProvider->loadUserByUsername('token');
    }
}