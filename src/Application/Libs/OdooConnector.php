<?php
namespace App\Application\Libs;

 require_once(__DIR__ .'/ripcord.php');
 
use Symfony\Component\Yaml\Yaml;
use Psr\Log\LoggerInterface;

class OdooConnector{

    protected $db;
    protected $email;
    protected $pwd;
    protected $url;

    public $odooUid;
    public $models;
    private $logger;

    function __construct($config, LoggerInterface $logger = null)
    {
        //$params = Yaml::parseFile(__DIR__ .'/../../../config.yaml');
        //$config = $params['Odoo'];
        $this->db = $config['db'];
        $this->email = $config['email'];
        $this->pwd = $config['pwd'];
        $this->url = $config['url'];

        $this->authenticate();
        $this->models = \ripcord::client("{$this->url}/xmlrpc/2/object");

        $this->logger = $logger;

    }

    function authenticate(){
        
        $common = \ripcord::client("{$this->url}/xmlrpc/2/common");
        $this->odooUid = $common->authenticate($this->db, $this->email, $this->pwd, []);
        if(!empty($this->odooUid)){
            //echo "Successfully sign in with the user id of : " . $this->odooUid . '</br>';
        }else{
            echo "Failed to sign in";
            return ["Failed to sign in"];
            exit;
        }
    }

    function countEmployees(){
        
        // an example of how to call the odoo API with keyword argument
        $count = $this->models->execute_kw($this->db, $this->odooUid, $this->pwd, 'hr.employee', 'search_count', [[]]);
        return (is_int($count))?$count:null;
    }

    function getEmployeeByBarcode($emp_code){

        $domain = [['barcode' ,'=', $emp_code]];
        $fields = ['id', 'user_id', 'active', 'gender','name'];
        $kwargs = ['fields' => $fields, 'domain' => $domain,'limit' => 1];
        $employees = $this->models->execute_kw($this->db, $this->odooUid, $this->pwd, 'hr.employee', 'search_read', [], $kwargs);
        return $employees;
    }

    function getEmployees(){

        $fields = ['id', 'user_id', 'active', 'gender','name'];
        $kwargs = ['fields' => $fields];
        $employees = $this->models->execute_kw($this->db, $this->odooUid, $this->pwd, 'hr.employee', 'search_read', [], $kwargs);
        return $employees;
    }

    function createEmployee($data){

        $values = [
            'name' => $data->full_name,
            'barcode' => $data->emp_code,
        ];
        
        $employee = $this->models->execute_kw($this->db, $this->odooUid, $this->pwd, 'hr.employee', 'create', [$values]);
        
        $this->logger->info(print_r($employee,1));
        if(is_array($employee) && $employee['faultCode']){
            if($employee['faultCode'] == 1){
                //Employe existe
                $msg = "Employee {$data->full_name} with presence id {$data->id} exist!\n";
                echo $msg;
                $this->logger->error($msg);
            }
            return null;
        }
        echo "Created employee {$data->full_name} with presence id {$data->id}!\n";
        return $employee;
    }

    function setAttendance($data){
        $empl = $this->getEmployeeByBarcode($data['emp_code']);
        $emplId = $empl[0]['id'];
        print_r($data);
        $fields = ['id', 'check_in', 'check_out'];
        $domain = [['employee_id' ,'=', $emplId]];
        if(isset($data['date_time_in'])){
            $domain[]= ['check_in' ,'=', $data['date_time_in']];
            $kwargs = ['fields' => $fields, 'domain' => $domain,'limit' => 1];
            $attendance = $this->models->execute_kw($this->db, $this->odooUid, $this->pwd, 'hr.attendance', 'search_read', [], $kwargs);
        }else if(isset($data['date_time_out'])){
            $domain[]= ['check_out' ,'=', $data['date_time_out']];
            $kwargs = ['fields' => $fields, 'domain' => $domain,'limit' => 1];
            $attendance = $this->models->execute_kw($this->db, $this->odooUid, $this->pwd, 'hr.attendance', 'search_read', [], $kwargs);
        }

        print_r($attendance);
        $vals = ['employee_id' => $emplId];
        if($data['date_time_in'])$vals['check_in']= $data['date_time_in'];
        if($data['date_time_out'])$vals['check_out']= $data['date_time_out'];
        
        if($attendance){
            $attendanceId = $attendance[0]['id'];
            $check = $this->models->execute_kw($this->db, $this->odooUid, $this->pwd, 'hr.attendance', 'write', [[$attendanceId],$vals]);
            
        }else{
            $check = $this->models->execute_kw($this->db, $this->odooUid, $this->pwd, 'hr.attendance', 'create', [$vals]);
        }
        print_r($check);
        return $check;
    }


    function setAttendanceIn($data){
        $empl = $this->getEmployeeByBarcode($data['emp_code']);
        $vals = [
            'employee_id' => $empl[0]['id'],
            'check_in'=> $data['punch_date'].' '.$data['punch_time']
        ];
        //var_dump($vals);
        $check_in = $this->models->execute_kw($this->db, $this->odooUid, $this->pwd, 'hr.attendance', 'create', [$vals]);
        return $check_in;
    }

    function setAttendanceOut($data){
        $empl = $this->getEmployeeByBarcode($data['emp_code']);
        $vals = [
            'employee_id' => $empl[0]['id'],
            'check_out'=> $data['punch_date'].' '.$data['punch_time']
        ];
        //var_dump($vals);
        $check_out = $this->models->execute_kw($this->db, $this->odooUid, $this->pwd, 'hr.attendance', 'create', [$vals]);
        return $check_out;
    }

}