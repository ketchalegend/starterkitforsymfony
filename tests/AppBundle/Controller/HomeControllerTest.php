<?php


namespace Tests\AppBundle\Controller;

use Liip\FunctionalTestBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testHomePageAndChangeColorPage()
    {
        $client = $this->makeClient();
        $client->request('GET', '/');
        $this->assertStatusCode(200, $client);


        $client = $this->makeClient();
        $client->request('GET', '/change_color');
        $this->assertStatusCode(200, $client);
    }
}