<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use Symfony\Component\Yaml\Yaml;

use App\Application\Libs\OdooConnector;
use App\Application\Libs\ZkConnector;
use App\Application\Libs\PgConnection;

abstract class Action
{
    protected LoggerInterface $logger;

    protected Request $request;

    protected Response $response;

    protected array $args;

    protected array $config;
    protected $pdo;
    protected $odooConnector;
    protected $zkConnector;
    protected $pgZkConnector;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->config = Yaml::parseFile(__DIR__ .'/../../../config.yaml');

        $this->pdo = PgConnection::get()->connect($this->config['PgzK']);
        $this->odooConnector = new OdooConnector($this->config['Odoo'], $this->logger);
        $this->zkConnector = new ZkConnector($this->config['Zk']);
    }

    /**
     * @throws HttpNotFoundException
     * @throws HttpBadRequestException
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;

        try {
            return $this->action();
        } catch (DomainRecordNotFoundException $e) {
            throw new HttpNotFoundException($this->request, $e->getMessage());
        }
    }

    /**
     * @throws DomainRecordNotFoundException
     * @throws HttpBadRequestException
     */
    abstract protected function action(): Response;

    /**
     * @return array|object
     */
    protected function getFormData()
    {
        return $this->request->getParsedBody();
    }

    /**
     * @return mixed
     * @throws HttpBadRequestException
     */
    protected function resolveArg(string $name)
    {
        if (!isset($this->args[$name])) {
            throw new HttpBadRequestException($this->request, "Could not resolve argument `{$name}`.");
        }

        return $this->args[$name];
    }

    /**
     * @param array|object|null $data
     */
    protected function respondWithData($data = null, int $statusCode = 200): Response
    {
        $payload = new ActionPayload($statusCode, $data);

        return $this->respond($payload);
    }

    protected function respond(ActionPayload $payload): Response
    {
        $json = json_encode($payload, JSON_PRETTY_PRINT);
        $this->response->getBody()->write($json);

        return $this->response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus($payload->getStatusCode());
    }
}
