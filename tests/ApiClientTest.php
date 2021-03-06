<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 18.11.17
 * Time: 7:03
 */

namespace SSitdikov\ATOL\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use SSitdikov\ATOL\Client\ApiClient;
use PHPUnit\Framework\TestCase;
use SSitdikov\ATOL\Code\ErrorCode;
use SSitdikov\ATOL\Exception\ErrorAuthBadRequestException;
use SSitdikov\ATOL\Exception\ErrorAuthGenTokenException;
use SSitdikov\ATOL\Object\Correction;
use SSitdikov\ATOL\Object\Info;
use SSitdikov\ATOL\Object\Receipt;
use SSitdikov\ATOL\Request\CorrectionRequest;
use SSitdikov\ATOL\Request\OperationRequest;
use SSitdikov\ATOL\Request\ReportRequest;
use SSitdikov\ATOL\Request\TokenRequest;
use SSitdikov\ATOL\Response\TokenResponse;

class ApiClientTest extends TestCase
{

    /**
     * @test
     */
    public function getApiClient()
    {
        $apiMock = $this->getMockBuilder(ApiClient::class)
            ->getMock();

        $apiMock->expects($this->once())->method('makeRequest')->willReturn(
            '{"code":"", "text":"", "token":"token"}'
        );

        $response = $apiMock->makeRequest(
            new TokenRequest('login', 'password')
        );

        $tokenResponse = new TokenResponse(\json_decode($response));
        $this->assertEquals('token', $tokenResponse->getToken());
    }

    /**
     * @test
     */
    public function getTokenRequest()
    {
        $client = $this->getMockBuilder(Client::class)->getMock();

        $token = md5(time());
        $response = new Response(200, [], '{"code":0, "text":"", "token":"' . $token . '"}');
        $client->expects($this->once())
            ->method('request')->willReturn($response);

        $api = new ApiClient($client);

        $request = new TokenRequest('login', 'password');
        $tokenResponse = $api->getToken($request);

        $this->assertEquals($token, $tokenResponse->getToken());
    }
    
    /**
     * @test
     */
    public function doOperation()
    {
        $client = $this->getMockBuilder(Client::class)->getMock();

        $uuid = md5(time());
        $response = new Response(
            200,
            [],
            '{"uuid":"'.$uuid.'", "error":null, "status":"", "timestamp":""}'
        );
        $client->expects($this->once())
            ->method('request')->willReturn($response);

        $api = new ApiClient($client);

        $request = new OperationRequest(
            '',
            '',
            '',
            new Receipt(),
            new Info('', '', ''),
            new TokenResponse(\json_decode('{"code":0, "text":"", "token":"token"}'))
        );
        $operationResponse = $api->doOperation($request);

        $this->assertEquals($uuid, $operationResponse->getUuid());
    }

    /**
     * @test
     */
    public function doCorrection()
    {
        $client = $this->getMockBuilder(Client::class)->getMock();

        $uuid = md5(time());
        $response = new Response(
            200,
            [],
            '{"uuid":"'.$uuid.'", "error":null, "status":"", "timestamp":""}'
        );
        $client->expects($this->once())
            ->method('request')->willReturn($response);

        $api = new ApiClient($client);

        $request = new CorrectionRequest(
            '',
            '',
            '',
            new Correction(),
            new Info('', '', ''),
            new TokenResponse(\json_decode('{"code":0, "text":"", "token":"token"}'))
        );
        $operationResponse = $api->doCorrection($request);

        $this->assertEquals($uuid, $operationResponse->getUuid());
    }

    /**
     * @test
     */
    public function getReport()
    {
        $client = $this->getMockBuilder(Client::class)->getMock();

        $uuid = md5(time());
        $response = new Response(
            200,
            [],
            '{"uuid":"'.$uuid.'", "error":null, "status":"", "payload":null, "timestamp":"", "group_code":"",' .
            '"daemon_code":"", "device_code":"", "callback_url":""}'
        );
        $client->expects($this->once())
            ->method('request')->willReturn($response);

        $api = new ApiClient($client);

        $request = new ReportRequest(
            '',
            '',
            new TokenResponse(\json_decode('{"code":0, "text":"", "token":"token"}'))
        );

        $report = $api->getReport($request);

        $this->assertEquals($uuid, $report->getUuid());
    }

    /**
     * @test
     */
    public function getClientException()
    {
        $mock = new MockHandler([
            new RequestException('', new Request('', ''))
        ]);
        $handler = HandlerStack::create($mock);

        $client = new Client(['handler' => $handler]);

        $api = new ApiClient($client);

        $request = new TokenRequest('', '');
        $this->expectException(RequestException::class);
        $api->makeRequest($request);
    }

    /**
     * @test
     */
    public function getErrorAuthBadRequestException()
    {
        $client = $this->getMockBuilder(Client::class)->getMock();


        $client->expects($this->once())
            ->method('request')->willThrowException(new ErrorAuthBadRequestException());

        $api = new ApiClient($client);

        $request = new TokenRequest('', '');
        $this->expectException(ErrorAuthBadRequestException::class);
        $api->makeRequest($request);
    }

    /**
     * @test
     */
    public function getApiClientBadResponse()
    {
        $client = $this->getMockBuilder(Client::class)->getMock();

        $client->expects($this->once())
            ->method('request')->willThrowException(new BadResponseException(
                '',
                new Request('', ''),
                new Response(400, [], 'text')
            ));

        $api = new ApiClient($client);
        try {
            $api->makeRequest(
                new TokenRequest('login', 'password')
            );
        } catch (BadResponseException $e) {
            $this->assertEquals('text', $e->getResponse()->getBody()->getContents());
        }
    }

    /**
     * @test
     */
    public function getApiClientBadException()
    {
        $client = $this->getMockBuilder(Client::class)->getMock();

        $client->expects($this->once())
            ->method('request')->willThrowException(new BadResponseException(
                '',
                new Request('', ''),
                new Response(400, [], '{"code":1, "text":"", "token":"token"}')
            ));

        $api = new ApiClient($client);
        try {
            $token = $api->getToken(
                new TokenRequest('login', 'password')
            );
            $this->assertEquals('token', $token->getToken());
        } catch (BadResponseException $e) {
            $this->assertEquals('text', $e->getResponse()->getBody()->getContents());
        }
    }
}
