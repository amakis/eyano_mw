<?php

declare(strict_types=1);

namespace App\Application\Api;

use App\Application\Actions\Action;
use App\Application\Libs\PgConnection;
use App\Application\Libs\OdooConnector;
use App\Application\Libs\ZkConnector;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;

abstract class AttendanceAction extends Action
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
    public function all() {
        $today = new \DateTime();
        $prevday = $today->sub(new \DateInterval("P30D"));
        $date = $prevday->format("Y-m-d");
        
        $stmt = $this->pdo->query(
                'SELECT at.time_card_id, at.punch_date, at.punch_time, at.emp_id, at.punch_state, pe.emp_code '
                . 'FROM att_payloadeffectpunch at '
                . 'LEFT JOIN personnel_employee pe ON at.emp_id = pe.id '
                . 'WHERE (at.att_date > \''.$date.'\') '
                . 'ORDER BY at.punch_datetime asc');
        $items = [];
        $lists = [];
        $input = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $items[] = $row;
            $time_card_id = $row['time_card_id'];
            //print_r($row);
            if(!isset($input[$time_card_id])){
                $input[$time_card_id] = [
                    'date_time_in' => null,
                    'date_time_out' => null,
                    'emp_code' => ''
                ];
            }
            if($row['punch_state'] == 0){
                if(!isset($lists[$time_card_id][0])){
                    $lists[$time_card_id][0] = $row;
                    $input[$time_card_id] = array_merge($input[$time_card_id],[
                        'date_time_in' => $row['punch_date'].' '.$row['punch_time'],
                        'emp_code' => $row['emp_code'],
                    ]);
                }
            }else{
                if(!isset($lists[$time_card_id][1])){
                    $lists[$time_card_id][1] = $row;
                    $input[$time_card_id] = array_merge($input[$time_card_id],[
                        'date_time_out' => $row['punch_date'].' '.$row['punch_time'],
                        'emp_code' => $row['emp_code'],
                    ]);
                }
            }
           
        }
        //print_r($input);
        //print_r($lists);
        $items[] = $this->create($input);
        exit;
        return $items;
    }
    /**
     * Return all rows in the stocks table
     * @return array
     */
    public function create($rows) {
        $items = [];
        foreach ($rows as $key => $row) {
            $items[] = $this->odooConnector->setAttendance($row);
        }
        // if($row['punch_state'] === '0'){
        //     $items[] = $this->odooConnector->setAttendanceIn($row);
        // }else{
        //     $items[] = $this->odooConnector->setAttendanceOut($row);
        // }
        return $items;
    }
}
