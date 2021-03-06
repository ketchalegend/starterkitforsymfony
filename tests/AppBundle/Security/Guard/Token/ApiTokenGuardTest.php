<?php

namespace Test\AppBundle\Security\Guard\Token;

use AppBundle\Factory\UserProviderFactory;
use AppBundle\Security\Guard\Token\ApiTokenGuard;
use Mockery\Mock;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Tests\BaseTestCase;

class ApiTokenGuardTest extends BaseTestCase
{
    /**
     * @var UserProviderFactory|Mock
     */
    protected $userProviderFactory;

    /**
     * @var ApiTokenGuard|Mock
     */
    protected $apiTokenGuard;

    public function setUp()
    {
        parent::setUp();
        $this->userProviderFactory = \Mockery::mock(UserProviderFactory::class);
        $this->apiTokenGuard = new ApiTokenGuard($this->userProviderFactory);
    }

    /**
     * This tests a bunch of invalid requests
     * @param Request $request
     * @dataProvider dataProviderForGetCreds
     */
    public function testGetCredentialsOnBadRequests(Request $request)
    {
        Assert::assertNull($this->apiTokenGuard->getCredentials($request));
    }

    /**
     * Tests that a valid request returns the array
     *
     */
    public function testGetCredentialsOnValidRequest()
    {
        $request = Request::create('/api/login_check', 'POST');
        $request->headers->set('Authorization', 'token');

        $creds = $this->apiTokenGuard->getCredentials($request);

        Assert::assertEquals($creds['token'], 'token');
        Assert::assertEquals($creds['type'], 'api');

    }

    /**
     * Asserts that it returns null which means the request is allowed to go through
     */
    public function testOnAuthenticationSuccess()
    {
        $token = \Mockery::mock(TokenInterface::class);

        $request = Request::create('/api/asdfasdf', 'POST');

        Assert::assertNull($this->apiTokenGuard->onAuthenticationSuccess($request, $token, 'api'));
    }


    /**
     * Provides a bunch of requests without authorization credenials
     * @return array
     */
    public function dataProviderForGetCreds()
    {
        $request = Request::create('/api/login_check', 'POST', [],[],[],[], json_encode(['type' => 'google', 'token' => null]));
        $request->attributes->set('_route', 'api_login');


        return [
            [Request::create('/api/blue', 'POST')],
            [Request::create('/api/red', 'GET')],
            [$request],
        ];
    }
}