<?php


namespace Digitick\Foundation\Fuse\Command\Http;


use Digitick\Foundation\Fuse\Command\AbstractCommand;
use Digitick\Foundation\Fuse\Command\Http\Exception\BadRequestException;
use Digitick\Foundation\Fuse\Command\Http\Exception\ForbiddenException;
use Digitick\Foundation\Fuse\Command\Http\Exception\InternalErrorException;
use Digitick\Foundation\Fuse\Command\Http\Exception\MethodNotAllowedException;
use Digitick\Foundation\Fuse\Command\Http\Exception\NotFoundException;
use Digitick\Foundation\Fuse\Command\Http\Exception\NotImplementedException;
use Digitick\Foundation\Fuse\Command\Http\Exception\ServerException;
use Digitick\Foundation\Fuse\Command\Http\Exception\TemporaryUnavailableException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Stream\Stream;

class HttpCommand extends AbstractCommand
{
    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';
    const HTTP_METHOD_PUT = 'PUT';
    const HTTP_METHOD_DELETE = 'DELETE';

    const HTTP_SCHEME_HTTP = 'http';
    const HTTP_SCHEME_HTTPS = 'https';

    /** @var  Client */
    protected $httpClient = null;

    protected $host;
    protected $port = 80;
    protected $path = '/';
    protected $headers;
    protected $scheme = self::HTTP_SCHEME_HTTP;
    protected $query;
    protected $user;
    protected $password;
    protected $method = self::HTTP_METHOD_GET;
    protected $body = '';

    protected $statusCode;
    protected $content;
    protected $responseHeaders;

    /**
     * HttpCommand constructor.
     * @param Client $httpClient
     */
    public function __construct($key)
    {
        parent::__construct($key);
    }

    /**
     * @return Client
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * @param Client $httpClient
     * @return $this
     */
    public function setHttpClient(Client $httpClient)
    {
        $this->httpClient = $httpClient;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param mixed $host
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param mixed $port
     * @return HttpCommand
     */
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param mixed $path
     * @return HttpCommand
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param mixed $headers
     * @return HttpCommand
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @param mixed $scheme
     * @return HttpCommand
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param mixed $query
     * @return HttpCommand
     */
    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     * @return HttpCommand
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     * @return HttpCommand
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     * @return HttpCommand
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     * @return HttpCommand
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return mixed
     */
    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }

    public function run()
    {
        if ($this->getHttpClient() === null) {
            throw new \RuntimeException();
        }

        $this->debug(sprintf("Create request with method=%s, scheme=%s, host=%s, port=%d, path=%s",
            $this->getMethod(),
            $this->getScheme(),
            $this->getHost(),
            $this->getPort(),
            $this->getPath()
        ));
        $request = $this->httpClient->createRequest($this->getMethod(), '', [

        ]);
        $request->setHost ($this->getHost ());
        $request->setPath ($this->getPath ());
        $request->setPort ($this->getPort() );
        $request->setScheme ($this->getScheme ());

        $request->setBody (Stream::factory($this->getBody ()));
        if ($this->headers != null) $request->setHeaders ($this->getHeaders ());
        if ($this->query != null) $request->setQuery ($this->getQuery ());

        try {
            $this->debug("Send request");
            $response = $this->httpClient->send($request);
        } catch (TransferException $exc) {
            throw $this->ExceptionFactory($exc);
        }

        $this->statusCode = $response->getStatusCode();
        $this->debug("Returned status code = " . $this->statusCode);
        $this->content = $response->getBody()->getContents();
        $this->responseHeaders = $response->getHeaders();

        return $this->content;
    }

    private function ExceptionFactory (TransferException $exc) {
        $this->error(sprintf("Transfer exception caught. Type : %s, status code = %s, message = %s",
                get_class($exc),
                $exc->getCode(),
                $exc->getMessage()
            )
        );
        $thrownException = null;

        if ($exc instanceof ClientException) {
            switch ($exc->getCode()) {

                case NotFoundException::STATUS_CODE :
                    $this->warning("Throw exception of type NotFoundException");
                    $thrownException = new NotFoundException ($exc->getMessage(), $exc->getCode(), $exc);
                    break;

                case ForbiddenException::STATUS_CODE :
                    $this->warning("Throw exception of type ForbiddenException");
                    $thrownException = new ForbiddenException($exc->getMessage(), $exc->getCode(), $exc);
                    break;

                case MethodNotAllowedException::STATUS_CODE :
                    $this->warning("Throw exception of type MethodNotAllowed");
                    $thrownException = new MethodNotAllowedException($exc->getMessage(), $exc->getCode(), $exc);
                    break;

                case BadRequestException::STATUS_CODE :
                    $this->warning("Throw exception of type MethodNotAllowed");
                    $thrownException = new BadRequestException($exc->getMessage(), $exc->getCode(), $exc);
                    break;

                default :
                    $this->error("Throw exception of type ClientException");
                    $thrownException = new \Digitick\Foundation\Fuse\Command\Http\Exception\ClientException($exc->getMessage(), $exc->getCode(), $exc);
            }
        } else if ($exc instanceof \GuzzleHttp\Exception\ServerException) {
            switch ($exc->getCode()) {
                case InternalErrorException::STATUS_CODE :
                    $this->error("Throw exception of type InternalErrorException");
                    $thrownException = new InternalErrorException($exc->getMessage(), $exc->getCode(), $exc);
                    break;

                case NotImplementedException::STATUS_CODE :
                    $this->error("Throw exception of type NotImplementedException");
                    $thrownException = new NotImplementedException($exc->getMessage(), $exc->getCode(), $exc);
                    break;

                case TemporaryUnavailableException::STATUS_CODE :
                    $this->error("Throw exception of type NotImplementedException");
                    $thrownException = new TemporaryUnavailableException($exc->getMessage(), $exc->getCode(), $exc);
                    break;

                default :
                    $this->error("Throw exception of type ServerException");
                    $thrownException = new ServerException ($exc->getMessage(), $exc->getCode(), $exc);
            }
        }

        return $thrownException;
    }
}