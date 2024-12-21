<?php

declare(strict_types=1);

namespace App\Application\Api;

use Psr\Http\Message\ResponseInterface as Response;

class CreateEmployeeAction extends EmployeeAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $items = $this->create();

        //$this->logger->info("Attendance list.");

        return $this->respondWithData($items);
    }
}
