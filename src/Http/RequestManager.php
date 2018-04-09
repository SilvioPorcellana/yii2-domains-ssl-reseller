<?php

namespace TheMavenSystem\DomainsSSLReseller\Http;

use Http\Client\Exception\TransferException;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\MessageFactory;


/**
 *
 * This class uses the php-http approach to discover an HTTP client and use that one to manage HTTP messages
 *
 *
 *
 * @see https://github.com/php-http
 *
 */
class RequestManager
{
    /**
     * @var \Http\Client\HttpClient
     */
    private $httpClient;
    /**
     * @var \Http\Message\MessageFactory
     */
    private $messageFactory;

    /**
     * This method creates and sends a request using the chosen MessageFactory and RequestFactory
     *
     * @param $method
     * @param $uri
     * @param array $headers
     * @param null $body
     * @param string $protocolVersion
     * @return mixed
     */
    public function sendRequest($method, $uri, array $headers = [], $body = null, $protocolVersion = '1.1')
    {
        $request = $this->getMessageFactory()->createRequest($method, $uri, $headers, $body, $protocolVersion);
        try {
            return $this->getHttpClient()->sendRequest($request);
        } catch (TransferException $e) {
            throw new TransferException('Error while sending the requesting: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }


    /**
     * @param HttpClient $httpClient
     * @return $this
     */
    public function setHttpClient(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
        return $this;
    }

    /**
     * @return HttpClient
     */
    protected function getHttpClient()
    {
        if ($this->httpClient === null) {
            $this->httpClient = HttpClientDiscovery::find();
        }
        return $this->httpClient;
    }

    /**
     * @param MessageFactory $messageFactory
     * @return RequestManager
     */
    public function setMessageFactory(MessageFactory $messageFactory)
    {
        $this->messageFactory = $messageFactory;
        return $this;
    }

    /**
     * @return \Http\Message\MessageFactory
     */
    private function getMessageFactory()
    {
        if ($this->messageFactory === null) {
            $this->messageFactory = MessageFactoryDiscovery::find();
        }
        return $this->messageFactory;
    }
}