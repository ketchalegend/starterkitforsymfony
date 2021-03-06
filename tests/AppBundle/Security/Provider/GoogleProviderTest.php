<?php
namespace Test\AppBundle\Security\Provider;

use AppBundle\Entity\User;
use AppBundle\Exception\ProgrammerException;
use AppBundle\Factory\GoogleClientFactory;
use AppBundle\Repository\UserRepository;
use AppBundle\Security\Provider\GoogleProvider;
use AppBundle\Service\User\RegisterService;
use AppBundle\Service\User\UserService;
use Mockery\Mock;
use PHPUnit\Framework\Assert;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Tests\BaseTestCase;

class GoogleProviderTest extends BaseTestCase
{
   

    /**
     * @var RegisterService|Mock
     */
    private $registerService;

    /**
     * @var \Google_Client|Mock
     */
    protected $googleClient;

    /**
     * @var GoogleProvider
     */
    protected $googleProvider;

    /**
     * @var UserService|Mock
     */
    protected $userService;


    protected function setUp()
    {
        parent::setUp();
        $this->registerService = \Mockery::mock(RegisterService::class);
        $googleClientFactory = \Mockery::mock(GoogleClientFactory::class);
        $this->googleClient = \Mockery::mock(\Google_Client::class);
        $googleClientFactory->shouldReceive('getGoogleClient')->once()->andReturn($this->googleClient);
        $this->userService = \Mockery::mock(UserService::class);

        $this->googleProvider = new GoogleProvider($this->registerService, $googleClientFactory, $this->userService);
    }

    /**
     * Test that if a new user auth with google that we register the user in our database
     */
    public function testNewUser()
    {
        $this->userService->shouldReceive('findByGoogleUserId')->with(32423323)->once()->andReturnNull();
        $this->userService->shouldReceive('findUserByEmail')->with('blue@gmail.com')->once()->andReturnNull();
        $token = 'asdfasdfasdfasdf';
        $this->googleClient->shouldReceive('verifyIdToken')->once()->with($token)->andReturn(['email' => 'blue@gmail.com', 'sub' => 32423323]);
        $this->registerService->shouldReceive('registerUser')->once()->with(\Mockery::type(User::class), RegisterService::SOURCE_TYPE_GOOGLE);
        $user = $this->googleProvider->loadUserByUsername($token);

        Assert::assertNotEmpty($user->getPlainPassword());
        Assert::assertEquals('blue@gmail.com',$user->getEmail());
        Assert::assertEquals(32423323, $user->getGoogleUserId());
    }

    /**
     * Tests if the if the user email is found in the database we return it and save google user id
     */
    public function testUserEmailFoundInDatabase()
    {
        $user = new User();
        $this->userService->shouldReceive('findByGoogleUserId')->with(32423323)->once()->andReturnNull();
        $this->userService->shouldReceive('findUserByEmail')->with('blue@gmail.com')->once()->andReturn($user);

        $token = 'asdfasdfasdfasdf';
        $this->googleClient->shouldReceive('verifyIdToken')->once()->with($token)->andReturn(['email' => 'blue@gmail.com', 'sub' => 32423323]);
        $this->registerService->shouldReceive('registerUser')->never()->withAnyArgs();
        $this->userService->shouldReceive('save')->with($user)->once();
        $returnedUser = $this->googleProvider->loadUserByUsername($token);

        Assert::assertEquals($user, $returnedUser);
        Assert::assertEquals(32423323, $user->getGoogleUserId());
    }

    public function testThatIfGoogleUserIdIsFoundUserIsReturned()
    {
        $user = new User();
        $token = 'asdfasdfasdfasdf';
        $this->userService->shouldReceive('findByGoogleUserId')->with(32423323)->once()->andReturn($user);
        $this->googleClient->shouldReceive('verifyIdToken')->once()->with($token)->andReturn(['email' => 'blue@gmail.com', 'sub' => 32423323]);
        $returnedUser = $this->googleProvider->loadUserByUsername($token);

        Assert::assertEquals($user, $returnedUser);

    }

    /**
     * Tests that if the google sdk throws an exception we transfer it to a UsernameNotFoundException
     */
    public function testLogicExceptionIsThrownTransformedToUsernameNotFoundException()
    {
        $token = 'asdfasdfasdfasdf';
        $this->googleClient->shouldReceive('verifyIdToken')->once()->with($token)->andThrow(new \LogicException('bad logic'));

        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionCode(ProgrammerException::GOOGLE_USER_PROVIDER_LOGIC_EXCEPTION);
        $this->expectExceptionMessage("Google AuthToken Did Not validate, ERROR MESSAGE bad logic");

        $this->googleProvider->loadUserByUsername($token);
    }

    /**
     * Tests that if there is an Exception thrown  we transfer it to a UsernameNotFoundException
     */
    public function testExceptionIsThrownTransformedToUsernameNotFoundException()
    {
        $token = 'asdfasdfasdfasdf';
        $this->googleClient->shouldReceive('verifyIdToken')->once()->with($token)->andThrow(new \Exception('bad logic'));

        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionCode(ProgrammerException::GOOGLE_USER_PROVIDER_EXCEPTION);
        $this->expectExceptionMessage("Google AuthToken Did Not validate, ERROR MESSAGE bad logic");

        $this->googleProvider->loadUserByUsername($token);

    }

    /**
     * Test refresh user must be an instance of Usr
     */
    public function testRefreshUserWithUnsupportedUserThrowsException()
    {
        $this->expectException(UnsupportedUserException::class);
        $this->googleProvider->refreshUser(new BlahUser());
    }

    /**
     * Test that refresh user return the same user that match the email provided
     */
    public function testRefreshUserValidUserReturns()
    {
        $user = new User();
        $user->setEmail('bluemoo@gmail.com');
        $this->userService->shouldReceive('findUserByEmail')->with('bluemoo@gmail.com')->once()->andReturn($user);

        /** @var User $userRefreshed */
        $userRefreshed =  $this->googleProvider->refreshUser($user);
        Assert::assertEquals($user->getEmail(), $userRefreshed->getEmail());

    }

    /**
     * Asserts that only class User is supported
     */
    public function testSupportedClass()
    {
        Assert::assertTrue($this->googleProvider->supportsClass(User::class));
        Assert::assertFalse($this->googleProvider->supportsClass(BlahUser::class));
    }
}

class BlahUser implements UserInterface {
    /**
     * @inheritDoc
     */
    public function getRoles()
    {
        // TODO: Implement getRoles() method.
    }

    /**
     * @inheritDoc
     */
    public function getPassword()
    {
        // TODO: Implement getPassword() method.
    }

    /**
     * @inheritDoc
     */
    public function getSalt()
    {
        // TODO: Implement getSalt() method.
    }

    /**
     * @inheritDoc
     */
    public function getUsername()
    {
        // TODO: Implement getUsername() method.
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }


}