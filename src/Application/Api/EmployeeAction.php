<?php

declare(strict_types=1);

namespace App\Application\Api;

use App\Application\Actions\Action;
use App\Application\Libs\PgConnection;
use App\Application\Libs\OdooConnector;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;

abstract class EmployeeAction extends Action
{
    protected $attendance;
    protected $pdo;
    protected $odooConnector;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }
    
    /**
     * Return all rows in the stocks table
     * @return array
     */
    public function create() {
        $zkEmployees = $this->zkConnector->getEmployees();
        $items = [];
        foreach ($zkEmployees->data as $key => $employee) {
            $items[] = $this->odooConnector->createEmployee($employee);
        }
        return $items;
    }
}
