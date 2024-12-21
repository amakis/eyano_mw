<?php

declare(strict_types=1);

namespace App\Application\Api;

use Psr\Http\Message\ResponseInterface as Response;

class ListAttendanceAction extends AttendanceAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $items = $this->all();

        $this->logger->info("Attendance list.");

        return $this->respondWithData($items);
    }
}
