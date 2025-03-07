<?php
class Actions{
    private $conn;
    function __construct(){
        require_once(realpath(__DIR__.'/../db-connect.php'));
        $this->conn = $conn;
    }
    /**
     * Class Actions
     */
    public function save_class(){
        foreach($_POST as $k => $v){
            if(!is_array($_POST[$k]) && !is_numeric($_POST[$k]) && !empty($_POST[$k])){
                $_POST[$k] = addslashes(htmlspecialchars($v));
            }
        }
        extract($_POST);

        if(!empty($id)){
            $check = $this->conn->query("SELECT id FROM `class_tbl` where `name` = '{$name}' and `id` != '{$id}' ");
            $stmt = $this->conn->prepare("UPDATE `class_tbl` set `name` = ? where `id` = ?");
            $stmt->bind_param("si", $name, $id);
            $stmt->execute();
        }else{
            $check = $this->conn->query("SELECT id FROM `class_tbl` where `name` = '{$name}' ");
            $stmt = $this->conn->prepare("INSERT INTO `class_tbl` (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            $stmt->execute();
        }
        if($check->num_rows > 0){
            return ['status' => 'error', 'msg' => 'Class Name Already Exists!'];
        }else{
            if(empty($id)){
                $_SESSION['flashdata'] = [ 'type' => 'success', 'msg' => "New Class has been added successfully!" ];
            }else{
                $_SESSION['flashdata'] = [ 'type' => 'success', 'msg' => "Class Data has been updated successfully!" ];
            }
            return [ 'status' => 'success'];
        }
    }
    public function delete_class(){
        extract($_POST);
        $stmt = $this->conn->prepare("DELETE FROM `class_tbl` where `id` = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if($stmt->affected_rows > 0){
            $_SESSION['flashdata'] = [ 'type' => 'success', 'msg' => "Class has been deleted successfully!" ];
            return [ "status" => "success" ];
        }else{
            $_SESSION['flashdata'] = [ 'type' => 'danger', 'msg' => "Class has failed to delete due to unknown reason!" ];
            return [ "status" => "error", "msg" => "Class has failed to delete!" ];
        }
    }
    public function list_class(){
        $stmt = $this->conn->prepare("SELECT * FROM `class_tbl` order by `name` ASC");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    public function get_class($id=""){
        $stmt = $this->conn->prepare("SELECT * FROM `class_tbl` where `id` = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    /**
     * Student Actions
     */
    public function save_student(){
        foreach($_POST as $k => $v){
            if(!is_array($_POST[$k]) && !is_numeric($_POST[$k]) && !empty($_POST[$k])){
                $_POST[$k] = addslashes(htmlspecialchars($v));
            }
        }
        extract($_POST);

        if(!empty($id)){
            $check = $this->conn->query("SELECT id FROM `students_tbl` where `name` = '{$name}' and `class_id` = '{$class_id}' and `id` != '{$id}' ");
            $stmt = $this->conn->prepare("UPDATE `students_tbl` set `name` = ?, `class_id` = ? where `id` = ?");
            $stmt->bind_param("sii", $name, $class_id, $id);
            $stmt->execute();
        }else{
            $check = $this->conn->query("SELECT id FROM `students_tbl` where `name` = '{$name}' and `class_id` = '{$class_id}' ");
            $stmt = $this->conn->prepare("INSERT INTO `students_tbl` (name, class_id) VALUES (?, ?)");
            $stmt->bind_param("si", $name, $class_id);
            $stmt->execute();
        }
        if($check->num_rows > 0){
            return ['status' => 'error', 'msg' => 'Student Name Already Exists!'];
        }else{
            if(empty($id)){
                $_SESSION['flashdata'] = [ 'type' => 'success', 'msg' => "New Student has been added successfully!" ];
            }else{
                $_SESSION['flashdata'] = [ 'type' => 'success', 'msg' => "Student Data has been updated successfully!" ];
            }
            return [ 'status' => 'success'];
        }
    }
    public function delete_student(){
        extract($_POST);
        $stmt = $this->conn->prepare("DELETE FROM `students_tbl` where `id` = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if($stmt->affected_rows > 0){
            $_SESSION['flashdata'] = [ 'type' => 'success', 'msg' => "Student has been deleted successfully!" ];
            return [ "status" => "success" ];
        }else{
            $_SESSION['flashdata'] = [ 'type' => 'danger', 'msg' => "Student has failed to delete due to unknown reason!" ];
            return [ "status" => "error", "msg" => "Student has failed to delete!" ];
        }
    }
    public function list_student(){
        $stmt = $this->conn->prepare("SELECT `students_tbl`.*, `class_tbl`.`name` as `class` FROM `students_tbl` inner join `class_tbl` on `students_tbl`.`class_id` = `class_tbl`.`id` order by `students_tbl`.`name` ASC");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    public function get_student($id=""){
        $stmt = $this->conn->prepare("SELECT `students_tbl`.*, `class_tbl`.`name` as `class` FROM `students_tbl` inner join `class_tbl` on `students_tbl`.`class_id` = `class_tbl`.`id` where `students_tbl`.`id` = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    public function attendanceStudents($class_id = "", $class_date = ""){
        if(empty($class_id) || empty($class_date))
            return [];
        $stmt = $this->conn->prepare("SELECT `students_tbl`.*, COALESCE((SELECT `status` FROM `attendance_tbl` where `student_id` = `students_tbl`.id and `class_date` = ?), 0) as `status` FROM `students_tbl` where `class_id` = ? order by `name` ASC");
        $stmt->bind_param("si", $class_date, $class_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    public function attendanceStudentsMonthly($class_id = "", $class_month = ""){
        if(empty($class_id) || empty($class_month))
            return [];
        $stmt = $this->conn->prepare("SELECT `students_tbl`.* FROM `students_tbl` where `class_id` = ? order by `name` ASC");
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        foreach($result as $k => $row){
            $att_stmt = $this->conn->prepare("SELECT `status`, `class_date` FROM `attendance_tbl` where `student_id` = ?");
            $att_stmt->bind_param("i", $row['id']);
            $att_stmt->execute();
            $att_qry = $att_stmt->get_result();
            foreach($att_qry as $att_row){
                $result[$k]['attendance'][$att_row['class_date']] = $att_row['status'];
            }
        }
        return $result;
    }
    public function save_attendance(){
        extract($_POST);
        $sql_values = "";
        $errors = "";
        foreach($student_id as $k => $sid){
            $stat = $status[$k] ?? 3;
            $check = $this->conn->query("SELECT id FROM `attendance_tbl` where `student_id` = '{$sid}' and `class_date` = '{$class_date}'");
            if($check->num_rows > 0){
                $result = $check->fetch_assoc();
                $att_id = $result['id'];
                try{
                    $update = $this->conn->query("UPDATE `attendance_tbl` set `status` = '{$stat}' where `id` = '{$att_id}'");
                }catch(Exception $e){
                    if(!empty($errors)) $errors .= "<br>";
                    $errors .= $e->getMessage();
                }
            }else{
                if(!empty($sql_values)) $sql_values .= ", ";
                $sql_values .= "( '{$sid}', '{$class_date}', '{$stat}' )";
            }
        }
        if(!empty($sql_values)){
            try{
                $sql =  $this->conn->query("INSERT INTO `attendance_tbl` ( `student_id`, `class_date`, `status` ) VALUES {$sql_values}");
            }catch(Exception $e){
                if(!empty($errors)) $errors .= "<br>";
                $errors .= $e->getMessage();
            }
        }
        if(empty($errors)){
            $resp['status'] = "success";
            $_SESSION['flashdata'] = [ "type" => "success", "msg" => "Class Attendance Data has been saved successfully." ];
        }else{
            $resp['status'] = "error";
            $resp['msg'] = $errors;
        }
        return $resp;
    }
    function __destruct()
    {
        if($this->conn)
        $this->conn->close(); 
    }
}
