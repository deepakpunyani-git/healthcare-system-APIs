<?php 


	class Api extends Rest {
		
		public function __construct() {
			parent::__construct();

		}

	
		/* Tokens */

		public function generateToken() {

			$request = json_decode($this->request,true);
			
			$email = $this->validateParameter('email', $request['param']['email'] , EMAIL);


			$pass = $this->validateParameter('pass', $request['param']['pass'], STRING);
			$device_id = $this->validateParameter('device_id', $request['param']['device_id'], STRING);
			$device_token = $this->validateParameter('device_token', $request['param']['device_token'], STRING);

			$password = md5(md5($pass)); 

			$email_check = 0;
			try {

				$where='';
				if ($email) {
			  		$where .= (($where=='')? ' ' : 'and' ) ." us.email  = '". $email."'";
			  		$email_check = 1;
				}


				$sql = "select 
							us.id,us.name,concat('". IMAGE_PATH ."',image) as image,email,dob,
							DATE_FORMAT(FROM_DAYS(DATEDIFF(now(),dob)), '%Y')+0 AS age,
							phone_number,
							weight,height,bmi,
							old_bmi,old_weight,
							father_name,gender,
							DATE_FORMAT(us.date_created, '%d/%M/%Y') as contact_date,
							address,city,ct.name as country,country_id,
							medical_history,special_comment,
							user_type,us.phone_number_verify,us.email_verify,user_blocked
					from users as us
					left join country as ct on(ct.id = us.country_id ) 
					where ". $where ." and password = '". $password ."'";

				$stmt = $this->dbConn->prepare($sql);
				$stmt->execute();
				$user = $stmt->fetch(PDO::FETCH_ASSOC);


				if(!is_array($user)) {
					$this->throwError(BAD_REQUEST, "Logins are incorrect");
				}

				if($user['image']==IMAGE_PATH){
					$user['image']='';
				}
				

				if( $user['email_verify'] == 0 && $email_check == 1 ) {
					$this->throwError(BAD_REQUEST, "email not verified");
				}


				if( $user['user_type'] != 0 ) {
					$this->throwError(BAD_REQUEST, "Not Patient");
				}

				if( $user['user_blocked'] == 1 ) {
					$this->throwError(BAD_REQUEST, "User is not activated. Please contact to admin.");
				}


				$user_id = $user['id'];


				/* Update Device Id  */
				$sql_device_udate = "UPDATE users SET device_id = '".$device_id."' , device_token = '".$device_token."'  WHERE id = '".$user_id."'";
				$stmt2 = $this->dbConn->prepare($sql_device_udate);
				$stmt2->execute();



				/*  Create Log  */
				$sql_log = "INSERT INTO user_login_log (user_id, device_id, login_time) VALUES ('".$user_id."', '".$device_id."', now())";
				$stmt3 = $this->dbConn->prepare($sql_log);
				$stmt3->execute();

	
				$paylod = [
					'iat' => time(),
					'iss' => 'localhost',
					'userId' => $user_id,
					'role' =>'user'

				];


				$token = JWT::encode($paylod, SECRETE_KEY);
				
				$data = ['token' => $token,'userId' => $user_id,'user_info' => $user];

				$this->setToken($user_id,$token);


				$this->returnResponse(SUCCESS, $data);
			} catch (Exception $e) {
				$this->throwError(BAD_REQUEST, $e->getMessage());
			}
		}

		public function generateStaffToken() {

			$request = json_decode($this->request,true);
			$username = $this->validateParameter('username', $request['param']['username'], STRING);
			$pass = $this->validateParameter('pass', $request['param']['pass'], STRING);
			$device_id = $this->validateParameter('device_id', $request['param']['device_id'], STRING);
			$password = md5(md5($pass)); 
	

			$device_token = $this->validateParameter('device_token', $request['param']['device_token'], STRING);

			try {



				$sql = "select id,name,concat('". IMAGE_PATH ."',image) as image,email,phone_number,user_type,user_blocked
					from users
					where username  = '". $username ."' and password = '". $password ."'";

				$stmt = $this->dbConn->prepare($sql);
				$stmt->execute();
				$user = $stmt->fetch(PDO::FETCH_ASSOC);

				if($user['image']==IMAGE_PATH){
					$user['image']='';
				}

				
				if(!is_array($user)) {
					$this->throwError(BAD_REQUEST, "username or password is incorrect");
				}


				if( $user['user_type'] != 2 ) {
					$this->throwError(BAD_REQUEST, "Not Staff");
				}



				if( $user['user_blocked'] == 1 ) {
					$this->throwError(BAD_REQUEST, "User is not activated. Please contact to admin.");
				}


				$user_id = $user['id'];


				/* Update Device Id  */
				$sql_device_udate = "UPDATE users SET device_id = '".$device_id."' , device_token = '".$device_token."'  WHERE id = '".$user_id."'";
				$stmt2 = $this->dbConn->prepare($sql_device_udate);
				$stmt2->execute();



				/*  Create Log  */
				$sql_log = "INSERT INTO user_login_log (user_id, device_id, login_time) VALUES ('".$user_id."', '".$device_id."', now())";
				$stmt3 = $this->dbConn->prepare($sql_log);
				$stmt3->execute();

	
				$paylod = [
					'iat' => time(),
					'iss' => 'localhost',
					'role' =>'staff',
					'userId' => $user_id
				];

				$token = JWT::encode($paylod, SECRETE_KEY);
				
				$data = ['token' => $token,'userId' => $user_id,'user_info' => $user];

				$this->setToken($user_id,$token);




				$this->returnResponse(SUCCESS, $data);
			} catch (Exception $e) {
				$this->throwError(BAD_REQUEST, $e->getMessage());
			}
		}

		public function generateAdminToken() {

			$request = json_decode($this->request,true);
			$username = $this->validateParameter('username', $request['param']['username'], STRING);
			$pass = $this->validateParameter('pass', $request['param']['pass'], STRING);
			$device_id = $this->validateParameter('device_id', $request['param']['device_id'], STRING);
			$password = md5(md5($pass)); 
			
			$device_token = $this->validateParameter('device_token', $request['param']['device_token'], STRING);

			try {


				$sql = "select id,name,concat('". IMAGE_PATH ."',image) as image,email,phone_number,user_type,user_blocked
					from users
					where username  = '". $username ."' and password = '". $password ."'";

				$stmt = $this->dbConn->prepare($sql);
				$stmt->execute();
				$user = $stmt->fetch(PDO::FETCH_ASSOC);

				if($user['image']==IMAGE_PATH){
					$user['image']='';
				}
				
				if(!is_array($user)) {
					$this->throwError(BAD_REQUEST, "username is incorrect");
				}


				if( $user['user_type'] != 1 ) {
					$this->throwError(BAD_REQUEST, "Not Admin");
				}



				if( $user['user_blocked'] == 1 ) {
					$this->throwError(BAD_REQUEST, "User is not activated. Please contact to admin.");
				}


				$user_id = $user['id'];


				/* Update Device Id  */
				$sql_device_udate = "UPDATE users SET device_id = '".$device_id."' , device_token = '".$device_token."'  WHERE id = '".$user_id."'";
				$stmt2 = $this->dbConn->prepare($sql_device_udate);
				$stmt2->execute();



				/*  Create Log  */
				$sql_log = "INSERT INTO user_login_log (user_id, device_id, login_time) VALUES ('".$user_id."', '".$device_id."', now())";
				$stmt3 = $this->dbConn->prepare($sql_log);
				$stmt3->execute();

	
				$paylod = [
					'iat' => time(),
					'iss' => 'localhost',
					'role' =>'admin',
					'userId' => $user_id
				];

				$token = JWT::encode($paylod, SECRETE_KEY);
				
				$data = ['token' => $token,'userId' => $user_id,'user_info' => $user];


				$this->setToken($user_id,$token);


				$this->returnResponse(SUCCESS, $data);
			} catch (Exception $e) {
				$this->throwError(BAD_REQUEST, $e->getMessage());
			}
		}

		public function setToken($user_id,$token){

			$token = md5($token);

			$sql = "select * from users_token where user_id  = ". $user_id;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$user = $stmt->fetch(PDO::FETCH_ASSOC);
			
			if(empty($user)) {

				$sql_token = "INSERT INTO users_token (user_id, token,date_online,status_online,date_online_status) VALUES ('".$user_id."', '".$token."',now(),1,now())";
				$stmt2 = $this->dbConn->prepare($sql_token);
				$stmt2->execute();

			}else{

				$sql_token = "UPDATE users_token SET token = '".$token."',date_online=now(),status_online=1,date_online_status=now() WHERE user_id ='". $user_id ."'";
				$stmt2 = $this->dbConn->prepare($sql_token);
				$stmt2->execute();

			}

		}

		public function destroyToken() {
		
			$token = $this->getBearerToken();
			$payload = JWT::decode($token, SECRETE_KEY, ['HS256']);

			$user_id = $payload->userId;

			$sql = "UPDATE users_token SET token = '',date_offline=now(),status_online=0 WHERE user_id ='". $user_id ."'";


			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$this->returnResponse(SUCCESS, "LOGOUT");
			
		}	

		public function OnlineStatusUpdate() {
		
			$this->userAccess(array('admin','staff','user'));


			$token = $this->getBearerToken();
			$payload = JWT::decode($token, SECRETE_KEY, ['HS256']);

			$user_id = $payload->userId;

			$sql = "UPDATE users_token SET date_online_status=now(),status_online=1 WHERE user_id ='". $user_id ."'";


			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$this->returnResponse(SUCCESS, "Status Updated");
			
		}	


		public function generateTokenAfterVerify($user_id,$msg) {


			$sql = "select 
						us.id,us.name,concat('". IMAGE_PATH ."',image) as image,email,dob,
						DATE_FORMAT(FROM_DAYS(DATEDIFF(now(),dob)), '%Y')+0 AS age,
						phone_number,
						weight,height,bmi,
						father_name,gender,
						DATE_FORMAT(us.date_created, '%d/%M/%Y') as contact_date,
						address,city,ct.name as country,country_id,
						medical_history,special_comment,
						user_type,us.phone_number_verify,us.email_verify,user_blocked
				from users as us
				left join country as ct on(ct.id = us.country_id ) 
				where us.id=".$user_id;

			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$user = $stmt->fetch(PDO::FETCH_ASSOC);
			
			if($user['image']==IMAGE_PATH){
				$user['image']='';
			}


			$paylod = [
				'iat' => time(),
				'iss' => 'localhost',
				'userId' => $user_id,
				'role' =>'user'

			];


			$token = JWT::encode($paylod, SECRETE_KEY);
			
			$data = ['token' => $token,'userId' => $user_id,'user_info' => $user, 'message'=>$msg];

			$this->setToken($user_id,$token);


			$this->returnResponse(SUCCESS, $data);
			
		}


		/* Preloads */
		public function getCountries() {
			try {

				$stmt = $this->dbConn->prepare("select id,name from country where status = 1 order by name");
				$stmt->execute();
				$country = $stmt->fetchAll(PDO::FETCH_ASSOC);
				if(!is_array($country)) {
					$this->throwError(BAD_REQUEST, "No Country Found.");
				}



				$this->returnResponse(SUCCESS, $country);
			} catch (Exception $e) {
				$this->throwError(BAD_REQUEST, $e->getMessage());
			}
		}


		public function PreLoads() {


			$sql1= "select id,name from lifestyle where status = 1 order by name";
			$stmt1 = $this->dbConn->prepare($sql1);
			$stmt1->execute();
			$lifestyle = $stmt1->fetch(PDO::FETCH_ASSOC);

			$sql2= "select id,name from food_habits where status = 1 order by name";
			$stmt2 = $this->dbConn->prepare($sql2);
			$stmt2->execute();
			$food_habits = $stmt2->fetch(PDO::FETCH_ASSOC);


			$appointment_status = APPOINTMENT_STATUS;
			$appointment_type = APPOINTMENT_TYPE;
			$unit = UNIT;
			$days = DAYS;



			$PreLoads = array(
				'food_habits' => $food_habits,
				'lifestyle' => $lifestyle,
				'unit' => $unit,
				'days' => $days,
				'appointment_status' => $appointment_status,
				'appointment_type' => $appointment_type

			);
				
			$this->returnResponse(SUCCESS, $PreLoads);

		}



		/*  Users  */
		public function blockUser() {

			$this->userAccess(array('admin','staff'));


			$request = json_decode($this->request,true);
			$user_id = $this->validateParameter('user_id', $request['param']['user_id'] , INTEGER);

			$sql= "UPDATE users SET user_blocked = 1 WHERE id ='". $user_id ."'";		

			$stmt = $this->dbConn->prepare($sql);
			$chk = $stmt->execute();

			$this->returnResponse(SUCCESS, "User Blocked");


		} 


		public function unblockUser() {

			$this->userAccess(array('admin','staff'));

			$request = json_decode($this->request,true);
			$user_id = $this->validateParameter('user_id', $request['param']['user_id'] , INTEGER);

			$sql= "UPDATE users SET user_blocked = 0 WHERE id ='". $user_id ."'";		

			$stmt = $this->dbConn->prepare($sql);
			$chk = $stmt->execute();

			$this->returnResponse(SUCCESS, "User Unblocked");


		} 


		public function blockUserFollowups() {

			$this->userAccess(array('admin','staff'));


			$request = json_decode($this->request,true);
			$user_id = $this->validateParameter('user_id', $request['param']['user_id'] , INTEGER);

			$sql= "UPDATE users SET follow_up_block = 1 WHERE id ='". $user_id ."'";		

			$stmt = $this->dbConn->prepare($sql);
			$chk = $stmt->execute();

			$this->returnResponse(SUCCESS, "Followups Blocked");


		} 


		public function unblockUserFollowups() {

			$this->userAccess(array('admin','staff'));

			$request = json_decode($this->request,true);
			$user_id = $this->validateParameter('user_id', $request['param']['user_id'] , INTEGER);

			$sql= "UPDATE users SET follow_up_block = 0 WHERE id ='". $user_id ."'";		

			$stmt = $this->dbConn->prepare($sql);
			$chk = $stmt->execute();

			$this->returnResponse(SUCCESS, "Followups Unblocked");


		} 


		public function getPatientDetails() {

			$this->userAccess(array('admin','staff'));


			$request = json_decode($this->request,true);
			$user_id = $this->validateParameter('patient_id', $request['param']['patient_id'] , INTEGER);

			
			$sql = "select 
						us.id,us.name,if(image!='',concat('". IMAGE_PATH ."',image) ,'') as image,email,dob,
						DATE_FORMAT(FROM_DAYS(DATEDIFF(now(),dob)), '%Y')+0 AS age,
						phone_number,
						weight,height,bmi,
						old_bmi,old_weight,
						father_name,gender,
						DATE_FORMAT(us.date_created, '%d/%M/%Y') as contact_date,
						address,city,ct.name as country,country_id,
						medical_history,special_comment,
						user_type,us.phone_number_verify,us.email_verify,user_blocked
				from users as us
				left join country as ct on(ct.id = us.country_id ) 
				where us.id =". $user_id ;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$user = $stmt->fetch(PDO::FETCH_ASSOC);

			if(empty($user)) {
				$this->returnResponse(BAD_REQUEST, "User not Found");
			}
				
			$res['user'] = $user;	
			

			$sql= "select 
						DATE_FORMAT(ap.appointment_date, '%a,%d %b') as appointment_date,
						ap.appointment_time,appointment_status,ap.id as appointment_id
						from appointments as ap
						where ap.user_id = ". $user_id ." 
						and ap.appointment_date >= '". date("Y-m-d") ."'
					order by ap.appointment_date asc,ap.appointment_time asc";
							

			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$res['UpcomingAppointments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

			$sql= "select 
						DATE_FORMAT(ap.appointment_date, '%a,%d %b') as appointment_date,
						ap.appointment_time,appointment_status,ap.id as appointment_id
						from appointments as ap
						where ap.user_id = ". $user_id ." 
						and ap.appointment_date < '". date("Y-m-d") ."'
					order by ap.appointment_date desc,ap.appointment_time desc";
							

			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$res['PastAppointments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

			$this->returnResponse(SUCCESS, $res);


		}


		public function getUserProfile() {

		 	$this->userAccess(array('user'));

			$request = json_decode($this->request,true);	
			$user_id = $this->userId;

			$sql = "select 
						us.id,us.name,if(image!='',concat('". IMAGE_PATH ."',image) ,'') as image,email,dob,
						DATE_FORMAT(FROM_DAYS(DATEDIFF(now(),dob)), '%Y')+0 AS age,
						phone_number,
						weight,height,bmi,
						start_weight,start_height,
						old_bmi,old_weight,
						father_name,gender,
						DATE_FORMAT(us.date_created, '%d/%M/%Y') as contact_date,
						address,city,ct.name as country,country_id,
						medical_history,special_comment,
						user_type,us.phone_number_verify,us.email_verify,user_blocked
				from users as us
				left join country as ct on(ct.id = us.country_id ) 
				where us.id =". $user_id ;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$user = $stmt->fetch(PDO::FETCH_ASSOC);

			if(empty($user)) {
				$this->returnResponse(BAD_REQUEST, "User not Found");
			}
				


			$this->returnResponse(SUCCESS, $user);


		}

		public function getSuperUserProfile() {

			$this->userAccess(array('admin','staff'));


			$request = json_decode($this->request,true);
			$user_id = $this->validateParameter('user_id', $request['param']['user_id'] , INTEGER);

			
			$sql = "select 
						id,name,if(image!='',concat('". IMAGE_PATH ."',image) ,'') as image
				from users as us
				where id =". $user_id ;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$user = $stmt->fetch(PDO::FETCH_ASSOC);

			if(empty($user)) {
				$this->returnResponse(BAD_REQUEST, "User not Found");
			}
				
			$this->returnResponse(SUCCESS, $user);

		}


		public function ChangePass() {	

			$this->userAccess(array('admin','user'));


			$request = json_decode($this->request,true);
			$user_id = $this->validateParameter('login_user_id', $request['param']['login_user_id'] , INTEGER);
			$pass = $this->validateParameter('pass', $request['param']['pass'], STRING);
			$pass2 = $this->validateParameter('pass2', $request['param']['pass2'], STRING);
			
			$password = md5(md5($pass)); 

			if($pass!=$pass2){
				$this->returnResponse(BAD_REQUEST, "Password not matched");

			}


			$sql= "select id,name from users where id =". $user_id ;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$user = $stmt->fetch(PDO::FETCH_ASSOC);

			if(empty($user)) {
				$this->returnResponse(BAD_REQUEST, "user not found");
			}


			$sql_device_udate = "UPDATE users SET password = '".$password."' WHERE id = '".$user_id."'";
			$stmt2 = $this->dbConn->prepare($sql_device_udate);
			$stmt2->execute();

			$this->returnResponse(SUCCESS, "Password Change");

		}


		public function PatientTestAdd(){

			$this->userAccess(array('admin','staff','user'));

			$request = json_decode($this->request,true);

			$patient_id = $this->validateParameter('patient_id', $request['param']['patient_id'] , INTEGER);

			$test_name = $this->validateParameter('test_name', $request['param']['test_name'], STRING);

			$description = $this->validateParameter('description', $request['param']['description'], STRING);

			$sql1= "select id from patient_test_details where test_name = '". $test_name ."' and patient_id =".$patient_id;
			$stmt1 = $this->dbConn->prepare($sql1);
			$stmt1->execute();
			$tests = $stmt1->fetch(PDO::FETCH_ASSOC);
			if(!empty($tests)) {
				$this->returnResponse(BAD_REQUEST, "Test already exists");
			}

			if($description!=''){	

			
				$sql = "INSERT INTO patient_test_details (patient_id, test_name, description, created_by, date_create) VALUES ('".$patient_id."', '".$test_name."', '".$description."', ". $this->userId .", now())";
				$stmt = $this->dbConn->prepare($sql);
				$stmt->execute();
			}
				
			$this->returnResponse(SUCCESS, "Test Added");

	
		}


		public function PatientTestEdit(){

			$this->userAccess(array('admin','staff','user'));

			$request = json_decode($this->request,true);
			$test_id = $this->validateParameter('test_id', $request['param']['test_id'] , INTEGER);

			$test_name = $this->validateParameter('test_name', $request['param']['test_name'], STRING);

			$description = $this->validateParameter('description', $request['param']['description'], STRING);

			$sql1= "select id,old_description from patient_test_details where test_name = '". $test_name ."' and id !=".$test_id;
			$stmt1 = $this->dbConn->prepare($sql1);
			$stmt1->execute();
			$tests = $stmt1->fetch(PDO::FETCH_ASSOC);
			if(!empty($tests)) {
				$this->returnResponse(BAD_REQUEST, "Test already exists");
			}


			$sql2= "select id,description,patient_id from patient_test_details where id =".$test_id;
			$stmt2 = $this->dbConn->prepare($sql2);
			$stmt2->execute();
			$tests = $stmt2->fetch(PDO::FETCH_ASSOC);
			if(empty($tests)) {
				$this->returnResponse(BAD_REQUEST, "Invaild Test!");
			}


			$patient_id  = $tests['patient_id'];
			$old_description = $tests['description'];

			$sql = "UPDATE patient_test_details 
						SET 
							test_name = '".$test_name."',
							description = '".$description."',
							old_description = '".$old_description."',
							updated_by = ". $this->userId .",
							date_update = now() 
						WHERE id =".$test_id;	
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			if($description!=''){	

				$sql = "INSERT INTO patient_test_logs (test_id, patient_id, test_name, description, date_create) VALUES ('".$test_id."', '".$patient_id."', '".$test_name."', '".$description."', now())";
				$stmt = $this->dbConn->prepare($sql);
				$stmt->execute();

			}	

			
			$this->returnResponse(SUCCESS, "Test Updated");
	
		}

		public function PatientTestDelete(){

			$this->userAccess(array('admin','staff','user'));

			$request = json_decode($this->request,true);
			$test_id = $this->validateParameter('test_id', $request['param']['test_id'] , INTEGER);
			$sql1= "DELETE FROM patient_test_details WHERE id =".$test_id;
			$stmt1 = $this->dbConn->prepare($sql1);
			$stmt1->execute();
			
			$sql1= "DELETE FROM patient_test_logs WHERE test_id =".$test_id;
			$stmt1 = $this->dbConn->prepare($sql1);
			$stmt1->execute();

			$this->returnResponse(SUCCESS, "Test Deleted");
	
		}


		public function PatientTestList(){

			$this->userAccess(array('admin','staff','user'));

			$request = json_decode($this->request,true);
			$patient_id = $this->validateParameter('patient_id', $request['param']['patient_id'] , INTEGER);

	

			$sql1= "select * from patient_test_details where patient_id =".$patient_id;
			$stmt1 = $this->dbConn->prepare($sql1);
			$stmt1->execute();
			$tests = $stmt1->fetchAll(PDO::FETCH_ASSOC);
			if(empty($tests)) {
				$this->returnResponse(BAD_REQUEST, "Test Not Found");
			}


			$this->returnResponse(SUCCESS, $tests);
	
		}


		public function RegUser() {
						
			$request = json_decode($this->request,true);
			$device_id = $this->validateParameter('device_id', $request['param']['device_id'], STRING);
			$device_token = $this->validateParameter('device_token', $request['param']['device_token'], STRING);


			$full_name = $this->validateParameter('full_name', $request['param']['full_name'], STRING);

			$country_id = $this->validateParameter('country_id', $request['param']['country_id'], INTEGER);

			$phone_number = $this->validateParameter('phone_number', $request['param']['phone_number'] , PHONE,false);

			$email = $this->validateParameter('email', $request['param']['email'] , EMAIL);

			$pass = $this->validateParameter('password', $request['param']['password'], STRING);

			$password = md5(md5($pass)); 

			
			$email_number_otp='';
			$email_otp_date = '';

			if(!empty($email)){	
				$sql_em= "select id from users where email ='". $email ."'";
				$stmt_em = $this->dbConn->prepare($sql_em);
				$stmt_em->execute();
				$em = $stmt_em->fetch(PDO::FETCH_ASSOC);

				if(!empty($em)) {
					$this->returnResponse(BAD_REQUEST, "Email already exists, Please reset password");
				}

				$email_number_otp= rand(111111,999999);
				$email_otp_date = date('Y-m-d H:i:s');

				$this->otp_email_send($email,$full_name,$email_number_otp);

			}	


			if($phone_number!=''){

				$sql_ph= "select id from users where phone_number ='". $phone_number ."'";
				$stmt_ph = $this->dbConn->prepare($sql_ph);
				$stmt_ph->execute();
				$ph = $stmt_ph->fetch(PDO::FETCH_ASSOC);

				if(!empty($ph)) {
					$this->returnResponse(BAD_REQUEST, "Phone number already exists");
				}

			}


			$sql = "INSERT INTO users (name, device_id,device_token, email,phone_number,country_id,password,date_created,email_otp,email_otp_date) VALUES ('".$full_name."','".$device_id."','".$device_token."','".$email."','".$phone_number."','".$country_id."','". $password ."', now(),'".$email_number_otp."','".$email_otp_date."')";


			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$this->returnResponse(SUCCESS, "Please verify your account");

		}


		public function RegUserResendEmailOtp() {
			$request = json_decode($this->request,true);

			$email = $this->validateParameter('email', $request['param']['email'] , EMAIL);

			$sql= "select id,name from users where email ='". $email."'";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$res = $stmt->fetch(PDO::FETCH_ASSOC);

			if(empty($res)) {
				$this->returnResponse(BAD_REQUEST, "Invaild Request");
			}

			$email_number_otp= rand(111111,999999);

			$sql = "UPDATE users 
						SET 
							email_otp = '".$email_number_otp."',
							email_otp_date = now()
						WHERE id = '".$res['id']."'";

			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$this->otp_email_send($email,$res['name'],$email_number_otp);
			$this->returnResponse(SUCCESS,"OTP Resend");

		}
		



		public function RegUserVerifyEmailOtp() {

			$request = json_decode($this->request,true);
			$email = $this->validateParameter('email', $request['param']['email'] , EMAIL);
			$otp = $this->validateParameter('otp', $request['param']['otp'], INTEGER);

			$sql= "select email_otp,id,email_otp_date,TIMESTAMPDIFF(MINUTE,email_otp_date,now()) as count_time from users where email = '". $email ."' and email_otp!=''";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$res = $stmt->fetch(PDO::FETCH_ASSOC);

			if(empty($res)) {
				$this->returnResponse(BAD_REQUEST, "Invaild Request");
			}

			if($res['count_time']>OTP_VALIDITY){

				$this->returnResponse(BAD_REQUEST, "OTP Expired");
			}
		

			if($res['email_otp'] != $otp){
				$this->returnResponse(BAD_REQUEST, "OTP Not Match");
			}



			$sql = "UPDATE users 
						SET 
							email_verify = 1
						WHERE id = '".$res['id']."'";

			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$this->generateTokenAfterVerify($res['id'],"Phone Number Verified");



		}



		public function RegUserResendPhoneOtp() {
			$request = json_decode($this->request,true);

			$phone = $this->validateParameter('phone', $request['param']['phone']);

			$sql= "select id,name from users where phone ='". $phone."'";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$res = $stmt->fetch(PDO::FETCH_ASSOC);

			if(empty($res)) {
				$this->returnResponse(BAD_REQUEST, "Invaild Request");
			}

			$phone_number_otp= rand(111111,999999);

			$sql = "UPDATE users 
						SET 
							phone_otp = '".$phone_number_otp."',
							phone_otp_date = now()
						WHERE id = '".$res['id']."'";

			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$this->otp_sms_send($phone,$res['name'],$phone_number_otp);
			$this->returnResponse(SUCCESS,"OTP Resend");

		}

		public function RegUserVerifyPhoneOtp() {

			$request = json_decode($this->request,true);
			$phone = $this->validateParameter('phone', $request['param']['phone']);
			$otp = $this->validateParameter('otp', $request['param']['otp'], INTEGER);

			$sql= "select _otp,id,email_otp_date,TIMESTAMPDIFF(MINUTE,email_otp_date,now()) as count_time from users where email = '". $email ."' and email_otp!=''";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$res = $stmt->fetch(PDO::FETCH_ASSOC);

			if(empty($res)) {
				$this->returnResponse(BAD_REQUEST, "Invaild Request");
			}

			if($res['count_time']>OTP_VALIDITY){

				$this->returnResponse(BAD_REQUEST, "OTP Expired");
			}
		

			if($res['email_otp'] != $otp){
				$this->returnResponse(BAD_REQUEST, "OTP Not Match");
			}



			$sql = "UPDATE users 
						SET 
							email_verify = 1
						WHERE id = '".$res['id']."'";

			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$this->generateTokenAfterVerify($res['id'],"Phone Number Verified");



		}



		public function UpdateSuperUser() {
			
			$this->userAccess(array('admin','staff'));
			$user_id = $this->userId;

			$request = json_decode($this->request,true);

			$full_name = $this->validateParameter('full_name', $request['param']['full_name'], STRING);
			
			$sql = "UPDATE users	
						SET 
							name = '".$full_name."'
						WHERE id =". $user_id;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$this->returnResponse(SUCCESS,'Profile Updated');

		}


		public function UpdateUser() {
			
			$this->userAccess(array('user'));
			$user_id = $this->userId;

			$sql= "select id,name,phone_number,email,weight,bmi,country_id,date_created,date_updated from users where id =". $user_id;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$res = $stmt->fetch(PDO::FETCH_ASSOC);

			if(empty($res)) {
				$this->returnResponse(BAD_REQUEST, "Invaild User");
			}

			$old_bmi = $res['bmi'];
			$old_weight = $res['weight'];
			$country_id = $res['country_id'];
			$date_log = ($res['date_updated'] != '0000-00-00 00:00:00')?$res['date_updated']:$res['date_created'];

			$request = json_decode($this->request,true);

			$full_name = $this->validateParameter('full_name', $request['param']['full_name'], STRING);

			$email = $this->validateParameter('email', $request['param']['email'] , EMAIL);


			$phone_number = $this->validateParameter('phone_number', $request['param']['phone_number'] , PHONE,false);

	

			
			$dob = $this->validateParameter('dob', $request['param']['dob'], STRING,false);

			$weight = $this->validateParameter('weight', $request['param']['weight'], STRING);
			$height = $this->validateParameter('height', $request['param']['height'], STRING);
			$bmi = $this->validateParameter('bmi', $request['param']['bmi'], STRING);


			$father_name = $this->validateParameter('father_name', $request['param']['father_name'], STRING,false);

			$gender = $this->validateParameter('gender', $request['param']['gender'], STRING,false);

			$address = $this->validateParameter('address', $request['param']['address'], STRING,false);

			$city = $this->validateParameter('city', $request['param']['city'], STRING,false);

			if($phone_number!=''){

				$sql_ph= "select id from users where phone_number ='". $phone_number ."'";
				$stmt_ph = $this->dbConn->prepare($sql_ph);
				$stmt_ph->execute();
				$ph = $stmt_ph->fetch(PDO::FETCH_ASSOC);

				if(!empty($ph)) {
					$this->returnResponse(BAD_REQUEST, "Phone number already exists");
				}

			}



			if(!empty($email)){	
				$sql_em= "select id from users where email ='". $email ."' and id!=".$user_id;
				$stmt_em = $this->dbConn->prepare($sql_em);
				$stmt_em->execute();
				$em = $stmt_em->fetch(PDO::FETCH_ASSOC);

				if(!empty($em)) {
					$this->returnResponse(BAD_REQUEST, "Email already exists ");
				}

				if($email!=$res['email']){

					$email_number_otp= rand(111111,999999);

					$sql = "UPDATE users 
								SET 
									email = '".$email."',
									email_otp = '".$email_number_otp."',
									email_verify = 0,
									email_otp_date = now()
								WHERE id = '".$user_id."'";

					$stmt = $this->dbConn->prepare($sql);
					$stmt->execute();

					$this->otp_email_send($res['email'],$res['name'],$email_number_otp);

				}

			}





			if($res['date_updated'] == '0000-00-00 00:00:00'){

				$sql = "UPDATE users	
						SET 
							start_height ='".$height."',
							start_weight ='".$weight."'
						WHERE id =". $user_id;
				$stmt = $this->dbConn->prepare($sql);
				$stmt->execute();
			}


			
			$sql = "UPDATE users	
						SET 
							name = '".$full_name."',
							phone_number = '". $phone_number ."',
							dob='".$dob."',
							height='".$height."',
							weight='".$weight."',
							bmi='".$bmi."',
							gender = '". $gender ."',
							old_bmi='".$old_bmi."',
							old_weight='".$old_weight."',
							father_name='".$father_name."',
							address='".$address."',
							city='".$city."',
							date_updated=now()
						WHERE id =". $user_id;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			
			$sql = "INSERT INTO user_logs (user_id,weight,changes,date_create,date_log) VALUES ('".$user_id."','".$old_weight."','Weight',now(),'". $date_log ."')";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();


			$sql = "INSERT INTO user_logs (user_id,bmi,changes,date_create,date_log) VALUES ('".$user_id."', '".$old_bmi."','BMI',now(),'". $date_log ."')";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$this->returnResponse(SUCCESS,'Profile Updated');

		}	


		public function UpdateUserMedicalHistory() {


			$request = json_decode($this->request,true);

			$this->userAccess(array('admin','staff','user'));

			$patient_id = $this->validateParameter('patient_id', $request['param']['patient_id'], INTEGER);

			$medical_history = $this->validateParameter('medical_history', $request['param']['medical_history'], STRING,false);

			$sql = "UPDATE users	
						SET 
							medical_history = '".$medical_history."'
						WHERE id = $patient_id";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();


			$sql = "INSERT INTO user_logs (user_id,medical_history,changes,date_log,date_create) VALUES ('".$patient_id."', '".$medical_history."','Medical History',now(),now())";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$this->returnResponse(SUCCESS,'Medical History Updated');


		}	


		public function UpdateUserSpecialComment() {

			$request = json_decode($this->request,true);

			$this->userAccess(array('admin','staff'));

			$patient_id = $this->validateParameter('patient_id', $request['param']['patient_id'], INTEGER);

			$special_comment = $this->validateParameter('special_comment', $request['param']['special_comment'], STRING);

			$sql = "UPDATE users	
						SET 
							special_comment = '".$special_comment."',
							date_updated=now()
						WHERE id = $patient_id";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();


			 $sql = "INSERT INTO user_logs (user_id,special_comment,changes,date_create,date_log) VALUES ('".$patient_id."', '".$special_comment."','Special Comment',now(),now())";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$tokens = array();

			$sql2= "select id,name,device_token from users where device_token!='' and id =". $patient_id ;
			$stmt2 = $this->dbConn->prepare($sql2);
			$stmt2->execute();
			$user = $stmt2->fetch(PDO::FETCH_ASSOC);
								
			$tokens[] = $user['device_token'];

			if(!empty($user)) {

				if(!empty($tokens)){
					$this->sendFirebaseMessage($tokens,$special_comment,NOTIFICATION_SPECIAL_COMMENT_TITLE,NOTIFICATION_SPECIAL_COMMENT_CLICK_ACTION);
				}

			}	

			$this->returnResponse(SUCCESS,'Special Comment Updated');

		}

		public function ForgotPassword() {

			$request = json_decode($this->request,true);
				
			$email = $this->validateParameter('email', $request['param']['email'] , EMAIL);

			$where='';
			$email_check = 0;
			if ($email) {
		  		$where .= (($where=='')? ' ' : 'and' ) ." email  = '". $email."'";
		  		$email_check = 1;
			}



			$sql = "select 	
							id,name,country_id,phone_number_verify,email_verify,user_blocked 
						from users where ". $where;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$user = $stmt->fetch(PDO::FETCH_ASSOC);

			if(!is_array($user)) {
				$this->throwError(BAD_REQUEST, "Invalid Email!");
			}


			if( $user['user_blocked'] == 1 ) {
				$this->throwError(BAD_REQUEST, "User is not activated. Please contact to admin.");
			}


			$otp=rand(111111,999999);
			$link = RESET_PASSWORD_URL.'?auth='.md5($user['id'].$otp);


		
			if($email_check ==1){

				$this->forgot_pass_email_send($email,$user['name'],$link);

			}



			$sql= "UPDATE users SET forgot_password_otp = '". $otp ."' , forgot_password_otp_date = now()  WHERE id =".$user['id'];

			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute(); 
			$this->returnResponse(SUCCESS,"Reset link sent on your email!");
			

		}



		public function AdminForgotPassword() {

			$request = json_decode($this->request,true);

			$email = $this->validateParameter('email', $request['param']['email'] , EMAIL);


			$email_check = 0;
		
			$where='';
			if ($email) {
		  		$where .= (($where=='')? ' ' : 'and' ) ." email  = '". $email."' and user_type='1'";
		  		$email_check = 1;
			}



			$sql = "select id,name from users where ". $where;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$user = $stmt->fetch(PDO::FETCH_ASSOC);

			if(!is_array($user)) {
				$this->throwError(BAD_REQUEST, "Invalid Email!");
			}


			$otp=rand(111111,999999);
			$link = RESET_PASSWORD_URL.'?auth='.md5($user['id'].$otp); 

			if($email_check ==1){

				$this->forgot_pass_email_send($email,$user['name'],$link);

			}

			$sql= "UPDATE users SET forgot_password_otp = '". $otp ."' , forgot_password_otp_date = now()  WHERE id =".$user['id'];

			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute(); 

			$this->returnResponse(SUCCESS,"Password Reset Link Sent!");
			

		}



		/* Report */

		public function PatientReportAdd(){

			$this->userAccess(array('admin','staff','user'));

			$request = json_decode($this->request,true);
			$patient_id = $this->validateParameter('patient_id', $request['param']['patient_id'] , INTEGER);

			$report_title = $this->validateParameter('report_title', $request['param']['report_title'], STRING);


			$sql1= "select id from patient_reports where report_title = '". $report_title ."' and user_id =".$patient_id;
			$stmt1 = $this->dbConn->prepare($sql1);
			$stmt1->execute();
			$Reports = $stmt1->fetch(PDO::FETCH_ASSOC);
			if(!empty($Reports)) {
				$this->returnResponse(BAD_REQUEST, "Report already exists");
			}

			$sql = "INSERT INTO patient_reports (user_id, report_title, date_created,create_by) VALUES ('".$patient_id."', '".$report_title."', now(),". $this->userId .")";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$report_id =  $this->dbConn->lastInsertId();



			$res['message'] = "Recipe Added";
			$res['report_id'] = $report_id;

			$this->returnResponse(SUCCESS, $res);

	
		}


		public function PatientReportEdit(){

			$this->userAccess(array('admin','staff','user'));

			$request = json_decode($this->request,true);
			$report_id = $this->validateParameter('report_id', $request['param']['report_id'] , INTEGER);

			$report_title = $this->validateParameter('report_title', $request['param']['report_title'], STRING);



			$sql1= "select id from patient_reports where report_title = '". $report_title ."' and id !=".$report_id;
			$stmt1 = $this->dbConn->prepare($sql1);
			$stmt1->execute();
			$tests = $stmt1->fetch(PDO::FETCH_ASSOC);
			if(!empty($tests)) {
				$this->returnResponse(BAD_REQUEST, "Report already exists");
			}



			$sql = "UPDATE patient_reports 
						SET 
							report_title = '".$report_title."',
							date_updated = now(),
							update_by =  ". $this->userId ."
						WHERE id =".$report_id;	
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();



			
			$this->returnResponse(SUCCESS, "Report Updated");
	
		}

		public function PatientReportDelete(){

			$this->userAccess(array('admin','staff','user'));


			$request = json_decode($this->request,true);
			$report_id = $this->validateParameter('report_id', $request['param']['report_id'] , INTEGER);

			$sql= "select report FROM patient_reports WHERE id =".$report_id;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if(!empty($reports)) {
				foreach($reports as $key => $report){
					if (file_exists(REPORT_FOLDER.$report['report'])){
						unlink(REPORT_FOLDER.$report['report']);
					}

				}
			}


			$sql1= "DELETE FROM patient_reports WHERE id =".$report_id;
			$stmt1 = $this->dbConn->prepare($sql1);
			$stmt1->execute();

			$this->returnResponse(SUCCESS, "Report Deleted");
	
		}


		public function PatientReportList(){

			$this->userAccess(array('admin','staff','user'));


			$request = json_decode($this->request,true);
			$patient_id = $this->validateParameter('patient_id', $request['param']['patient_id'] , INTEGER);

			$sql1= "select id,report_title,	
						if(report!='',concat('". REPORT_PATH ."',report) ,'') as report, 
						DATE_FORMAT(date_uploaded, '%d %M %Y') as date_uploaded,
						if(date_uploaded!='0000-00-00 00:00:00',DATE_FORMAT(date_uploaded, '%h:%i %p'),'') as time_uploaded 
					from patient_reports where user_id =".$patient_id;
			$stmt1 = $this->dbConn->prepare($sql1);
			$stmt1->execute();
			$reports = $stmt1->fetchAll(PDO::FETCH_ASSOC);
			if(empty($reports)) {
				$this->returnResponse(BAD_REQUEST, "Reports Not Found");
			}


			$this->returnResponse(SUCCESS, $reports);
	
		}

		


		/* Patients */
		public function getAllpatients() {	

			$this->userAccess(array('admin','staff'));

		
			$request = json_decode($this->request,true);
			$where = "where user_type = '0'";

			$appointment_date = $this->validateParameter('appointment_date', $request['param']['appointment_date'] , STRING,FALSE);
			$name = $this->validateParameter('name', $request['param']['name'] , STRING,FALSE);
			
			if($appointment_date!=''){

				$where .= ' and ap.appointment_date = "'.$appointment_date.'"';

			}


			if($name!=''){
				$where .= ' and ( us.name like "'.$name.'%" or ct.name like "'.$name.'%" )';
			}


			$sql= "select 
							us.id,us.name, 
							if(image!='',concat('". IMAGE_PATH ."',image) ,'') as image,
							email,us.phone_number,user_blocked,
							ct.name as country
					from users as us
					left join country as ct on(ct.id = us.country_id )
					left join appointments as ap on(ap.user_id = us.id ) "
					. $where . ' group by us.id order by us.name';
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if(empty($patients)) {
				$this->returnResponse(BAD_REQUEST, "Patients not Found");
			}


			$this->returnResponse(SUCCESS, $patients);

		}

		public function TotalPatients() {

			$this->userAccess(array('admin','staff','user'));

		
			$request = json_decode($this->request,true);
			$where = "where user_type = '0' and user_blocked = '0'";

			$sql= "select count(id) as total_patients from users ".$where;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$user = $stmt->fetch(PDO::FETCH_ASSOC);
			if(empty($user)) {
				$this->returnResponse(BAD_REQUEST, "Patients not Found");
			}

			$this->returnResponse(SUCCESS, $user);

		}

		/* Staff */
		public function getAllStaff() {
		

			$this->userAccess(array('admin'));

			$request = json_decode($this->request,true);
			$where = "where user_type = '2'";
		

			$sql= "select id,name,
						if(image!='',concat('". IMAGE_PATH ."',image) ,'') as image,	
						username from users ".$where;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$Satff = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if(empty($Satff)) {
				$this->returnResponse(BAD_REQUEST, "Satff not Found");
			}

			$this->returnResponse(SUCCESS, $Satff);

		}


		public function UpdateStaffLoginCredentials() {

			$this->userAccess(array('admin','staff'));

			$request = json_decode($this->request,true);
			$user_id = $this->validateParameter('staff_id', $request['param']['staff_id'] , INTEGER);
			$pass = $this->validateParameter('pass', $request['param']['pass'], STRING);
			$username = $this->validateParameter('username', $request['param']['username'], STRING);
			
			$password = md5(md5($pass)); 



			$sql= "select id,name from users where id =". $user_id ;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$user = $stmt->fetch(PDO::FETCH_ASSOC);

			if(empty($user)) {
				$this->returnResponse(BAD_REQUEST, "staff not found");
			}


			$sql_device_udate = "UPDATE users SET username = '". $username ."',  password = '".$password."' WHERE id = '".$user_id."'";
			$stmt2 = $this->dbConn->prepare($sql_device_udate);
			$stmt2->execute();

			$this->returnResponse(SUCCESS, "Staff Updated");

		}


		/* Notifications */

		public function sendNotification() {	

			$this->userAccess(array('admin','staff'));

			$request = json_decode($this->request,true);
			$sender_id = $this->validateParameter('sender_id', $request['param']['sender_id'] , INTEGER);
			$receiver_ids = $this->validateParameter('receiver_ids', $request['param']['receiver_ids'] , STRING);
			$notification = $this->validateParameter('notification', $request['param']['notification'], STRING);


			$receiver_ids_arr = explode(",", $receiver_ids);
			$tokens=array();
			foreach ($receiver_ids_arr as $key => $receiver_id) {


				$sql2= "select id,name,device_token from users where device_token!='' and id =". $receiver_id ;
				$stmt2 = $this->dbConn->prepare($sql2);
				$stmt2->execute();
				$user = $stmt2->fetch(PDO::FETCH_ASSOC);
									
				$tokens[] = $user['device_token'];


				if(!empty($user)) {
					$sql = "INSERT INTO notifications (notification_type,sender_id, receiver_id, notification , date_created) VALUES (0,'".$sender_id."', '".$receiver_id."', '".$notification."', now())";
					$stmt = $this->dbConn->prepare($sql);
					$stmt->execute();
				}

			}	

			if(!empty($tokens)){
				$this->sendFirebaseMessage($tokens,$notification,NOTIFICATION_TITLE,NOTIFICATION_CLICK_ACTION);
			}
				
			$this->returnResponse(SUCCESS, "Notification Sent");


		}

		public function PatientNotificationList() {

			$this->userAccess(array('user'));

			$request = json_decode($this->request,true);
			
			$sql= "select id,notification_type,appointment_id,reminder_id,notification, 
			DATE_FORMAT(date_created, '%d %M %Y') as date_created,
			DATE_FORMAT(date_created, '%h:%i %p') as time_created
			from notifications where receiver_id =". $this->userId ." order by id" ;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
								


			if(empty($notifications)) {
				$this->returnResponse(BAD_REQUEST, "Notifications not found");

			}
				
			$this->returnResponse(SUCCESS, $notifications);

		}


		/* Medicine */

		public function MedicineList() {

			$this->userAccess(array('admin','staff'));


			$request = json_decode($this->request,true);

			$medicine_name = $this->validateParameter('medicine_name', $request['param']['medicine_name'], STRING,FALSE);
			$where = '';
			if($medicine_name!=''){

				$where = " where medicine_name like '". $medicine_name."%'";
			}

			$sql= "select id,medicine_name from medicine_list ".$where;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute(); 
			$medicine = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if(empty($medicine)) {
				$this->returnResponse(BAD_REQUEST, "Medicine not Found");
			}

			$this->returnResponse(SUCCESS, $medicine);

		}


		public function addMedicine() {

			$this->userAccess(array('admin','staff'));


			$request = json_decode($this->request,true);
			$created_by = $this->validateParameter('login_user_id', $request['param']['login_user_id'] , INTEGER);
			$medicine_name = $this->validateParameter('medicine_name', $request['param']['medicine_name'], STRING);

			$sql1= "select * from medicine_list where medicine_name = '". $medicine_name ."'";
			$stmt1 = $this->dbConn->prepare($sql1);
			$stmt1->execute();
			$medicines = $stmt1->fetch(PDO::FETCH_ASSOC);
			if(!empty($medicines)) {
				$this->returnResponse(BAD_REQUEST, "Medicine already exists");
			}

			$sql = "INSERT INTO medicine_list (created_by, medicine_name , date_created) VALUES ('".$created_by."', '".$medicine_name."', now())";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$this->returnResponse(SUCCESS, "Medicine Added");

		}


		public function updateMedicine() {


			$this->userAccess(array('admin','staff'));


			$request = json_decode($this->request,true);
			$updated_by = $this->validateParameter('login_user_id', $request['param']['login_user_id'] , INTEGER);
			$medicine_name = $this->validateParameter('medicine_name', $request['param']['medicine_name'], STRING);
			$medicine_id = $this->validateParameter('medicine_id', $request['param']['medicine_id'] , INTEGER);

			$sql1= "select * from medicine_list where medicine_name = '". $medicine_name ."' and id!=".$medicine_id;
			$stmt1 = $this->dbConn->prepare($sql1);
			$stmt1->execute();
			$medicine = $stmt1->fetch(PDO::FETCH_ASSOC);

			if(!empty($medicine)) {
				$this->returnResponse(BAD_REQUEST, "Medicine already exists");
			}


			$sql = "UPDATE medicine_list 
						SET 
							medicine_name = '".$medicine_name."',
							date_updated = now(),
							updated_by = '".$updated_by."'  
						WHERE id = '".$medicine_id."'";

			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$this->returnResponse(SUCCESS, "Medicine Update");

		}



		public function deleteMedicine() {

			$this->userAccess(array('admin','staff'));


			$request = json_decode($this->request,true);
			$medicine_ids = $this->validateParameter('medicine_ids', $request['param']['medicine_ids'] , STRING);

			$sql1= "delete from medicine_list where id in(".$medicine_ids.")";
			$stmt1 = $this->dbConn->prepare($sql1);
			$stmt1->execute();
			$this->returnResponse(SUCCESS, "Medicine Deleted");

		}



		/* reminder */

		public function addReminder() {

			$this->userAccess(array('admin','staff'));

			$request = json_decode($this->request,true);
			$medicine_id = $this->validateParameter('medicine_id', $request['param']['medicine_id'] , INTEGER);
			$appointment_id  = $this->validateParameter('appointment_id', $request['param']['appointment_id'] , INTEGER);
			$date_start = $this->validateParameter('date_start', $request['param']['date_start'], STRING);
			$date_end = $this->validateParameter('date_end', $request['param']['date_end'], STRING);
			$time_dosage = $this->validateParameter('time_dosage', $request['param']['time_dosage'], STRING);
			$dosage = $this->validateParameter('dosage', $request['param']['dosage'], STRING);
			$days = $this->validateParameter('days', $request['param']['days'], STRING);
			$created_by = $this->userId;

			$sql = "INSERT INTO dosage_reminder (appointment_id,medicine_id, created_by, date_start , date_end,time_dosage,dosage,days,date_created) VALUES ('".$appointment_id."','".$medicine_id."', '".$created_by."', '".$date_start."','".$date_end."','".$time_dosage."','".$dosage."', '".$days."', now())";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$this->returnResponse(SUCCESS, "Reminder Added");

		}

		public function editReminder() {

			$this->userAccess(array('admin','staff'));

			$request = json_decode($this->request,true);
			$reminder_id = $this->validateParameter('reminder_id', $request['param']['reminder_id'] , INTEGER);
			$medicine_id = $this->validateParameter('medicine_id', $request['param']['medicine_id'] , INTEGER);
			$date_start = $this->validateParameter('date_start', $request['param']['date_start'], STRING);
			$date_end = $this->validateParameter('date_end', $request['param']['date_end'], STRING);
			$time_dosage = $this->validateParameter('time_dosage', $request['param']['time_dosage'], STRING);
			$dosage = $this->validateParameter('dosage', $request['param']['dosage'], STRING);
			$days = $this->validateParameter('days', $request['param']['days'], STRING);
			$updated_by = $this->userId;

			$sql = "UPDATE dosage_reminder	
						SET 
							medicine_id = $medicine_id,
							date_start='".$date_start."',
							date_end='".$date_end."',
							time_dosage='".$time_dosage."',
							dosage='".$dosage."',
							days='".$days."',
							date_updated=now(),
							updated_by=$updated_by 
						WHERE id = $reminder_id";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$this->returnResponse(SUCCESS, "Reminder Updated");

		}

		public function ReminderList() {
			
			$this->userAccess(array('admin','staff'));


			$request = json_decode($this->request,true);
			$appointment_id = $this->validateParameter('appointment_id', $request['param']['appointment_id'] , INTEGER);
			
			$sql= "select 
							dr.id,medicine_id,medicine_name,date_start,date_end,time_dosage,dosage,days,days as days_name
							from dosage_reminder as dr
							left join medicine_list as mh on(mh.id = dr.medicine_id)
						where appointment_id =  ".$appointment_id;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute(); 
			$reminder = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if(empty($reminder)) {
				$this->returnResponse(BAD_REQUEST, "Reminders not Found");
			}

			$res = array();
			foreach ($reminder as $key => $value) {

				if(!empty($value['days'])){	
					$days = explode(",",$value['days']);
				 	if(count($days) == 7){

				 		$value['days_name'] = 'All Days'; 

					}else{


				 		
				 		$days_name='';
				 		foreach($days as $d){
				 			$DAYS = DAYS;
				 			if($days_name==''){
				 				$days_name = $DAYS[$d];
				 			}else{
				 				$days_name .=','. $DAYS[$d];
				 			}	
				 		}

				 		$value['days_name'] = $days_name;
				 	}


				 }else{
					$value['days_name'] = ['days_name'];
				 }	

				$res[] = $value;
			}


			$this->returnResponse(SUCCESS, $res);

		}


		public function DeleteReminder() {

			$this->userAccess(array('admin','staff'));

			$request = json_decode($this->request,true);
			$reminder_id  = $this->validateParameter('reminder_id', $request['param']['reminder_id'] , INTEGER);
			

			$sql = "delete from dosage_reminder where id = ".$reminder_id;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$this->returnResponse(SUCCESS, "Reminder Deleted");

		}



		public function ReminderDetails() {

			$this->userAccess(array('admin','staff','user'));

			$request = json_decode($this->request,true);
			$reminder_id = $this->validateParameter('reminder_id', $request['param']['reminder_id'] , INTEGER);
			
			$sql= "select 
							medicine_name,date_start,date_end,time_dosage,dosage,days,counter,days as days_name
							from dosage_reminder as dr
							left join medicine_list as mh on(mh.id = dr.medicine_id)
						where dr.id =  ".$reminder_id;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute(); 
			$reminder = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if(empty($reminder)) {
				$this->returnResponse(BAD_REQUEST, "Reminder not Found");
			}


			$res = array();
			foreach ($reminder as $key => $value) {

				if(!empty($value['days'])){	
					$days = explode(",",$value['days']);
				 	if(count($days) == 7){

				 		$value['days_name'] = 'All Days'; 

					}else{


				 		
				 		$days_name='';
				 		foreach($days as $d){
				 			$DAYS = DAYS;
				 			if($days_name==''){
				 				$days_name = $DAYS[$d];
				 			}else{
				 				$days_name .=','. $DAYS[$d];
				 			}	
				 		}

				 		$value['days_name'] = $days_name;
				 	}


				 }else{
					$value['days_name'] = ['days_name'];
				 }	

				$res[] = $value;
			}


			$this->returnResponse(SUCCESS, $res);

		}


		public function ReminderCounterUpdate() {

			$this->userAccess(array('user'));

			$request = json_decode($this->request,true);
			$reminder_id = $this->validateParameter('reminder_id', $request['param']['reminder_id'] , INTEGER);
			
			$sql= "UPDATE dosage_reminder SET counter = (counter) + 1 WHERE id = ".$reminder_id;

			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute(); 



			$this->returnResponse(SUCCESS,"Counter Updated");

		}


		public function PatientAllReminders() {
							
			$request = json_decode($this->request,true);
			$this->userAccess(array('admin','staff','user'));
			$patient_id = $this->validateParameter('patient_id', $request['param']['patient_id'] , INTEGER);

			$sql= "select 
               dr.id,medicine_name,days,dosage
               ,DATE_FORMAT(time_dosage, '%h:%i %p') as time_dosage
               from dosage_reminder as dr
               left join medicine_list as mh on(mh.id = dr.medicine_id)
               left join appointments as ap on(ap.id = dr.appointment_id)
               where DATEDIFF(date_end,now()) >= 0 and user_id =  ".$patient_id. 
               " order by time_dosage asc ";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute(); 
			$reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if(empty($reminders)) {
				$this->returnResponse(BAD_REQUEST, "Reminder not Found");
			}


			$res = array();
			foreach ($reminders as $key => $value) {

				if(!empty($value['days'])){	
					$days = explode(",",$value['days']);
				 	if(count($days) == 7){

				 		$value['days_name'] = 'All Days'; 

					}else{


				 		
				 		$days_name='';
				 		foreach($days as $d){
				 			$DAYS = DAYS;
				 			if($days_name==''){
				 				$days_name = $DAYS[$d];
				 			}else{
				 				$days_name .=','. $DAYS[$d];
				 			}	
				 		}

				 		$value['days_name'] = $days_name;
				 	}


				 }else{
					$value['days_name'] = ['days_name'];
				 }	

				$res[] = $value;
			}


			$this->returnResponse(SUCCESS, $res);

		}


		public function PatientTodayReminders() {
							
			$request = json_decode($this->request,true);
			$this->userAccess(array('admin','staff','user'));
			$patient_id = $this->validateParameter('patient_id', $request['param']['patient_id'] , INTEGER);
   
   			$day = date('w');

			$sql= "select 
               dr.id,medicine_name,days,dosage
               ,DATE_FORMAT(time_dosage, '%h:%i %p') as time_dosage
               from dosage_reminder as dr
               left join medicine_list as mh on(mh.id = dr.medicine_id)
               left join appointments as ap on(ap.id = dr.appointment_id)
               where DATEDIFF(date_end,now()) >= 0 and user_id =  ".$patient_id. 
               " and find_in_set('". $day ."',days) order by time_dosage asc ";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute(); 
			$reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if(empty($reminders)) {
				$this->returnResponse(BAD_REQUEST, "Reminder not Found");
			}


			$res = array();
			foreach ($reminders as $key => $value) {

				if(!empty($value['days'])){	
					$days = explode(",",$value['days']);
				 	if(count($days) == 7){

				 		$value['days_name'] = 'All Days'; 

					}else{


				 		
				 		$days_name='';
				 		foreach($days as $d){
				 			$DAYS = DAYS;
				 			if($days_name==''){
				 				$days_name = $DAYS[$d];
				 			}else{
				 				$days_name .=','. $DAYS[$d];
				 			}	
				 		}

				 		$value['days_name'] = $days_name;
				 	}


				 }else{
					$value['days_name'] = ['days_name'];
				 }	

				$res[] = $value;
			}


			$this->returnResponse(SUCCESS, $res);

		}

		/* Appointments */

		public function appointmentAdd() {	


			$this->userAccess(array('user'));

			$request = json_decode($this->request,true);


			$patient_name = $this->validateParameter('patient_name', $request['param']['patient_name'], STRING);

			$phone_number = $this->validateParameter('phone_number', $request['param']['phone_number'] , PHONE);

			$appointment_date = $this->validateParameter('appointment_date', $request['param']['appointment_date'] , STRING);


			$appointment_time = $this->validateParameter('appointment_time', $request['param']['appointment_time'] , STRING);

		
		  	$appointment_type = $this->validateParameter('appointment_type', $request['param']['appointment_type'] , STRING);


		  	$user_id = $this->validateParameter('user_id', $request['param']['user_id'] , INTEGER);


			$message = $this->validateParameter('message', $request['param']['message'], STRING);



			$sql = "INSERT INTO appointments (user_id, patient_name, phone_number, appointment_date, appointment_time, appointment_type, message,  date_created) VALUES ('".$user_id."'  , '".$patient_name."'  , '".$phone_number."'  , '".$appointment_date."'  , '".$appointment_time."'  , '".$appointment_type."'  , '".$message."' ,  now())";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
	
			$this->returnResponse(SUCCESS,'Appointment added');

		}	



		public function UpcomingAppointments() {

			$this->userAccess(array('admin','staff'));

			$request = json_decode($this->request,true);
			$appointment_date = $this->validateParameter('appointment_date', $request['param']['appointment_date'] , STRING,FALSE);


			if($appointment_date!=''){


				if(date("Y-m-d") > $appointment_date){

					$this->returnResponse(BAD_REQUEST, "Appointment date must be greater than or equal to today's date");
				}

				$where = 'where us.user_blocked = 0 and ap.appointment_date = "'.$appointment_date.'"';
			}else{
			
				$appointment_date = date('Y-m-d');

				$where = 'where us.user_blocked = 0 and ap.appointment_date >= "'.$appointment_date.'"';
			}	

			$name = $this->validateParameter('name', $request['param']['name'] , STRING,FALSE);
			
	

			if($name!=''){
				$where .= ' and (ap.patient_name like "'.$name.'%" or ct.name like "'.$name.'%" )';
			}


			$sql= "select 
						ap.patient_name,us.name as register_user_name,
						DATE_FORMAT(ap.appointment_date, '%d/%m/%Y') as appointment_date,
						ap.appointment_time,appointment_status,ap.id as appointment_id,ap.user_id as patient_id
						from appointments as ap
					left join users as us on(us.id = ap.user_id)
					left join country as ct on(ct.id = us.country_id)
					". $where ."  order by ap.appointment_date ,ap.appointment_time";
							

			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$UpcomingAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if(empty($UpcomingAppointments)) {
				$this->returnResponse(BAD_REQUEST, "Upcoming Appointment not Found");
			}

			$this->returnResponse(SUCCESS, $UpcomingAppointments);


		}


		public function PastAppointments() {

			$this->userAccess(array('admin','staff'));

			$request = json_decode($this->request,true);
			$appointment_date = $this->validateParameter('appointment_date', $request['param']['appointment_date'] , STRING,FALSE);
			$name = $this->validateParameter('name', $request['param']['name'] , STRING,FALSE);
			
			
			if($appointment_date!=''){
				if($appointment_date >= date("Y-m-d")){

					$this->returnResponse(BAD_REQUEST, "Appointment date must be less than to today's date");

				}


				$where = 'where us.user_blocked = 0 and ap.appointment_date = "'.$appointment_date.'"';

			}else{

				$appointment_date = date('Y-m-d',strtotime(date('Y-m-d') . ' -1 day'));

				$where = 'where us.user_blocked = 0 and ap.appointment_date < "'.$appointment_date.'"';			
			}
			




			if($name!=''){
				$where .= ' and (ap.patient_name like "'.$name.'%" or ct.name like "'.$name.'%" )';
			}


			$sql= "select 
						ap.patient_name,us.name as register_user_name,
						DATE_FORMAT(ap.appointment_date, '%d/%m/%Y') as appointment_date,
						ap.appointment_time,appointment_status,ap.id as appointment_id,ap.user_id as patient_id
						from appointments as ap
					left join users as us on(us.id = ap.user_id)
					left join country as ct on(ct.id = us.country_id)
					". $where ."  order by ap.appointment_date desc,ap.appointment_time desc";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$PastAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if(empty($PastAppointments)) {
				$this->returnResponse(BAD_REQUEST, "Past Appointment not Found");
			}

			$this->returnResponse(SUCCESS, $PastAppointments);


		}



		public function AllAppointmentList() {

			 $this->userAccess(array('admin','staff'));

			$request = json_decode($this->request,true);
			$appointment_date = $this->validateParameter('appointment_date', $request['param']['appointment_date'] , STRING,FALSE);
			$name = $this->validateParameter('name', $request['param']['name'] , STRING,FALSE);
			
			$appointment_date = ($appointment_date =='')? date('Y-m-d'):$appointment_date;

			$where = 'where us.user_blocked = 0 and ap.appointment_date = "'.$appointment_date.'"';

			if($name!=''){
				$where .= ' and (ap.patient_name like "'.$name.'%" or ct.name like "'.$name.'%" )';
			}


			$sql= "select 
						ap.patient_name,us.name as register_user_name,ct.name as country_name,
						DATE_FORMAT(ap.appointment_date, '%d/%m/%Y') as appointment_date,
						ap.appointment_time,appointment_status,ap.id as appointment_id,ap.user_id as patient_id,
						if(image!='',concat('". IMAGE_PATH ."',image) ,'') as image
						from appointments as ap
					left join users as us on(us.id = ap.user_id)
					left join country as ct on(ct.id = us.country_id)
					". $where ."  order by ap.appointment_date,ap.appointment_time";
							

			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$appointment = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if(empty($appointment)) {
				$this->returnResponse(BAD_REQUEST, "appointment not Found");
			}

			$this->returnResponse(SUCCESS, $appointment);



		}


		public function confirmAppointment(){

			$this->userAccess(array('admin','staff'));

			$request = json_decode($this->request,true);
			$appointment_id = $this->validateParameter('appointment_id', $request['param']['appointment_id'] , STRING);


			$sql= "UPDATE appointments SET appointment_status = 1 WHERE id in (".$appointment_id.")";

			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute(); 

			$this->AppointmentNotification($appointment_id,"2","Appointment Confirmed");

			$this->returnResponse(SUCCESS, "Appointment(s) Confirmed");
		} 


		public function rejectAppointment(){

			$this->userAccess(array('admin','staff'));

			$request = json_decode($this->request,true);
			$appointment_id = $this->validateParameter('appointment_id', $request['param']['appointment_id'] , STRING);

			$sql= "UPDATE appointments SET appointment_status = 2 WHERE id in (".$appointment_id.")";

			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute(); 

			$this->AppointmentNotification($appointment_id,"3","Appointment Rejected");
			$this->returnResponse(SUCCESS, "Appointment(s) Rejected");
		} 

		public function CompleteAppointment(){

			$this->userAccess(array('admin','staff'));

			$request = json_decode($this->request,true);
			$appointment_id = $this->validateParameter('appointment_id', $request['param']['appointment_id'] , STRING);

			$sql= "UPDATE appointments SET appointment_status = 3 WHERE id in (".$appointment_id.")";

			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute(); 

			$this->AppointmentNotification($appointment_id,"4","Appointment Completed");

			$this->returnResponse(SUCCESS, "Appointment(s) Completed");

		}

		public function AppointmentNotification($appointment_id,$type,$notification){

			$tokens = array();

			$sql = "select 
							user_id,appointment_date,ap.id,device_token 
							from appointments as ap
							left join users as us on (us.id = ap.user_id) 
							where ap.id in (".$appointment_id.") 
							order by appointment_date asc";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if(!empty($appointments)){

				foreach ($appointments as $key => $value) {   
   
					$user_id = $value['user_id'];

					$sql = "INSERT INTO notifications (notification_type,sender_id, receiver_id, appointment_id,notification , date_created) VALUES ('". $type ."',0, '".$user_id."','".$value['id']."','".$notification."', now())";
					$stmt = $this->dbConn->prepare($sql);
			        $stmt->execute();

			        if($type == 4){
			        	
			        	$sql= "UPDATE users SET last_appointment = '". $value['appointment_date'] ."' WHERE id =".$user_id;
			        	$stmt = $this->dbConn->prepare($sql);
			        	$stmt->execute();

			        }


			        if($value['device_token']!=''){
			        	$tokens[] = $value['device_token'];
			        }
			    }    

			}	



			if(!empty($tokens)){

				if($type==2){
					$this->sendFirebaseMessage($tokens,$notification,NOTIFICATION_APPOINTMENT_CONFIRM_TITLE,NOTIFICATION_APPOINTMENT_CONFIRM_CLICK_ACTION);
				}


				if($type==3){
					$this->sendFirebaseMessage($tokens,$notification,NOTIFICATION_APPOINTMENT_REJECT_TITLE,NOTIFICATION_APPOINTMENT_REJECT_CLICK_ACTION);
				}

				if($type==4){
					$this->sendFirebaseMessage($tokens,$notification,NOTIFICATION_APPOINTMENT_COMPLETE_TITLE,NOTIFICATION_APPOINTMENT_COMPLETE_CLICK_ACTION);
				}

			}
		}	


		public function PatientUpcomingAppointments() {

			$this->userAccess(array('user'));

			$request = json_decode($this->request,true);
			$patient_id = $this->userId;

			$today = date('Y-m-d');

			$sql= "select 
						ap.patient_name,us.name as register_user_name,ap.appointment_type,	
						DATE_FORMAT(ap.appointment_date, '%d/%m/%Y') as appointment_date,
						ap.appointment_time,appointment_status,ap.id as appointment_id
						from appointments as ap
					left join users as us on(us.id = ap.user_id)
					left join country as ct on(ct.id = us.country_id)
					where ap.user_id = ". $patient_id ." and ap.appointment_date >= '". $today ."'  order by ap.appointment_date asc,ap.appointment_time asc";
							

			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$UpcomingAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if(empty($UpcomingAppointments)) {
				$this->returnResponse(BAD_REQUEST, "You have no scheduled appointments. Book one?");
			}

			$this->returnResponse(SUCCESS, $UpcomingAppointments);


		}


		public function PatientPastAppointments() {

			$this->userAccess(array('user'));

			$request = json_decode($this->request,true);
			$patient_id = $this->userId;
			$today = date('Y-m-d');

			$sql= "select 
						ap.patient_name,us.name as register_user_name,ap.appointment_type,	
						DATE_FORMAT(ap.appointment_date, '%d, %M %Y') as appointment_date,
						ap.appointment_time,appointment_status,ap.id as appointment_id
						from appointments as ap
					left join users as us on(us.id = ap.user_id)
					left join country as ct on(ct.id = us.country_id)
					where ap.user_id = ". $patient_id ." and ap.appointment_date < '". $today ."'  order by ap.appointment_date desc,ap.appointment_time desc";
							

			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$PastAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if(empty($PastAppointments)) {
				$this->returnResponse(BAD_REQUEST, "You have no scheduled appointments. Book one?");
			}

			$this->returnResponse(SUCCESS, $PastAppointments);


		}


		public function rescheduleAppointment(){

			$this->userAccess(array('user'));
			$request = json_decode($this->request,true);

			$appointment_id = $this->validateParameter('appointment_id', $request['param']['appointment_id'] , INTEGER);

			$appointment_date = $this->validateParameter('appointment_date', $request['param']['appointment_date'] , STRING);

			$appointment_time = $this->validateParameter('appointment_time', $request['param']['appointment_time'] , STRING);


			$sql= "UPDATE appointments SET appointment_date = '". $appointment_date ."',appointment_time = '". $appointment_time ."' WHERE id =". $appointment_id;

			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute(); 


			$this->returnResponse(SUCCESS, "Appointment Rescheduled");
		} 



		/* static page */

		public function staticPages(){

			$res=array(
				'award_and_achievement' => 'http://4squarelogic.in/webpage/aa.html',
				'faq' => 'http://4squarelogic.in/webpage/faq.html',
				'international recognition' => 'http://4squarelogic.in/webpage/ir.html',
				'vision' => 'http://4squarelogic.in/webpage/vision.html',
				'our_surgeon' => 'http://4squarelogic.in/webpage/oursurgeon.html',
				'about_us' => 'http://4squarelogic.in/webpage/about.html',
				'sugery_types' => 'http://4squarelogic.in/webpage/sugerytypes.html'
			);


			$this->returnResponse(SUCCESS, $res);

		}


		/* Recipes */

		public function RecipesList(){

			$sql= "select 	
							rp.id, title, concat(LEFT(rs.details, 25),'...') as details, 
							if(image!='',concat('". IMAGE_PATH ."',rp.image) ,'') as image
						from recipes as rp
						left join recipe_steps rs on (rs.recipe_id = rp.id)
					GROUP by rs.recipe_id order BY id";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if(empty($recipes)) {
				$this->returnResponse(BAD_REQUEST, "Recipes not Found");
			}

			$this->returnResponse(SUCCESS, $recipes);

		}	


		public function RecipeDetails(){


			$res = array();

			$request = json_decode($this->request,true);
			$recipe_id = $this->validateParameter('recipe_id', $request['param']['recipe_id'] , INTEGER);


			$sql= "select  
								id,title,description, if(image!='',concat('". IMAGE_PATH ."',image) ,'') as image
						from recipes
						where id = ".$recipe_id;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$recipe = $stmt->fetchAll(PDO::FETCH_ASSOC);

			$res['recipe'] = $recipe;



			if(empty($recipe)) {
				$this->returnResponse(BAD_REQUEST, "Recipe not Found");
			}


			$sql2= "select id,ingredient_name,amount from recipe_ingredients where recipe_id = ".$recipe_id;
			$stmt2 = $this->dbConn->prepare($sql2);
			$stmt2->execute();
			$ingredients = $stmt2->fetchAll(PDO::FETCH_ASSOC);

			$res['ingredients'] = $ingredients;

			
			
			$sql3= "select id,details from recipe_steps where recipe_id = ".$recipe_id;
			$stmt3 = $this->dbConn->prepare($sql3);
			$stmt3->execute();
			$steps = $stmt3->fetchAll(PDO::FETCH_ASSOC);

			$res['steps'] = $steps;


			

			$sql4= "select id,if(image!='',concat('". IMAGE_PATH ."',image) ,'') as image from recipe_images where recipe_id = ".$recipe_id;
			$stmt4 = $this->dbConn->prepare($sql4);
			$stmt4->execute();
			$images = $stmt4->fetchAll(PDO::FETCH_ASSOC);

			$res['images'] = $images;



			$this->returnResponse(SUCCESS, $res);

		}	


		public function RecipeAdd(){

			$this->userAccess(array('admin','staff'));

			$request = json_decode($this->request,true);
			$title = $this->validateParameter('title', $request['param']['title'] , STRING);

			$description = $this->validateParameter('description', $request['param']['description'] , STRING,false);

			$user_id = $this->userId;

			$ingredients = isset($request['param']['ingredients'])?$request['param']['ingredients']:'';


			$steps = isset($request['param']['steps'])?$request['param']['steps']:'';



			$sql = "INSERT INTO recipes (title,description, create_by,date_create) VALUES ('".$title."', '". $description ."' ,'".$user_id."' ,  now())";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$recipe_id =  $this->dbConn->lastInsertId();
			if(!empty($ingredients)){

				foreach ($ingredients as $key => $value) {
				
					$sql = "INSERT INTO recipe_ingredients (recipe_id, ingredient_name,amount) VALUES ('".$recipe_id."', '".$value['ingredient_name']."' , '".$value['amount']."')";
					$stmt = $this->dbConn->prepare($sql);
					$stmt->execute();
				}

			}

			if(!empty($steps)){

				foreach ($steps as $key => $value) {
				
					$sql = "INSERT INTO recipe_steps (recipe_id, details) VALUES ('".$recipe_id."', '".$value['details']."')";
					$stmt = $this->dbConn->prepare($sql);
					$stmt->execute();
				}

			}


			$res['message'] = "Recipe Added";
			$res['recipe_id'] = $recipe_id;

			$this->returnResponse(SUCCESS, $res);


		}


		public function RecipeEdit(){

			$this->userAccess(array('admin','staff'));

			$request = json_decode($this->request,true);
			$recipe_id = $this->validateParameter('recipe_id', $request['param']['recipe_id'] , INTEGER);

			$title = $this->validateParameter('title', $request['param']['title'] , STRING);

			$description = $this->validateParameter('description', $request['param']['description'] , STRING,false);


			$user_id = $this->userId;


			$steps = isset($request['param']['steps'])?$request['param']['steps']:'';
			$sql= "UPDATE recipes SET title = '". $title ."',  description = '". $description ."', update_by = '". $user_id ."', date_update = now() WHERE id =".$recipe_id;
	
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			

			if(!empty($steps)){

				foreach ($steps as $key => $value) {
				
					$sql= "UPDATE recipe_steps SET details = '". $value['details'] ."' WHERE id =".$value['id'];
					$stmt = $this->dbConn->prepare($sql);
					$stmt->execute();
				}

			}


			$ingredients = isset($request['param']['ingredients'])?$request['param']['ingredients']:'';

			
			if(!empty($ingredients)){

				foreach ($ingredients as $key => $value) {

					$sql= "UPDATE recipe_ingredients SET ingredient_name = '". $value['ingredient_name'] ."', amount = '". $value['amount'] ."' WHERE id =".$value['id'];
					$stmt = $this->dbConn->prepare($sql);
					$stmt->execute();
				}

			}


			$this->returnResponse(SUCCESS, "Recipe Updated");


		}	


		public function UpdateRecipeIngredients(){

			$this->userAccess(array('admin','staff'));

			$request = json_decode($this->request,true);
			$ingredients = isset($request['param']['ingredients'])?$request['param']['ingredients']:'';

			
			if(!empty($ingredients)){

				foreach ($ingredients as $key => $value) {

					$sql= "UPDATE recipe_ingredients SET ingredient_name = '". $value['ingredient_name'] ."', amount = '". $value['amount'] ."' WHERE id =".$value['id'];
					$stmt = $this->dbConn->prepare($sql);
					$stmt->execute();
				}

			}

			$this->returnResponse(SUCCESS, "Recipe Ingredients Updated");


		}	

		public function RecipeDelete(){

			$this->userAccess(array('admin','staff'));

			$request = json_decode($this->request,true);
			$recipe_id = $this->validateParameter('recipe_id', $request['param']['recipe_id'] , INTEGER);
			$sql= "DELETE FROM recipes WHERE id =".$recipe_id;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$sql= "DELETE FROM recipe_steps WHERE recipe_id =".$recipe_id;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$sql= "DELETE FROM recipe_ingredients WHERE recipe_id =".$recipe_id;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();


			$sql= "select image FROM recipe_images WHERE recipe_id =".$recipe_id;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if(!empty($images)) {
				foreach($images as $key => $image){
					if (file_exists(IMAGE_FOLDER.$image['image'])){
						unlink(IMAGE_FOLDER.$image['image']);
					}
				}
			}


			$sql= "DELETE FROM recipe_images WHERE recipe_id =".$recipe_id;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$this->returnResponse(SUCCESS, "Recipe Deleted");
	
		}

		public function RecipeRemoveImage(){

			$this->userAccess(array('admin','staff'));

			$request = json_decode($this->request,true);
			$image_ids = $this->validateParameter('image_ids', $request['param']['image_ids'] , STRING);

			$sql= "select image FROM recipe_images WHERE id in (".$image_ids.")";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if(!empty($images)) {
				foreach ($images as $key => $image) {
					if($image['image']!=''){
						if (file_exists(IMAGE_FOLDER.$image['image'])){
							unlink(IMAGE_FOLDER.$image['image']);
						}	
					}	
				}
			}

			
			$sql= "DELETE FROM recipe_images WHERE id in (".$image_ids.")";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$this->returnResponse(SUCCESS, "Image(s) Removed");
		}


		public function RecipeRemoveIngredients(){

			$this->userAccess(array('admin','staff'));

			$request = json_decode($this->request,true);
			$ingredient_ids = $this->validateParameter('ingredient_ids', $request['param']['ingredient_ids'] , STRING);

			
			$sql= "DELETE FROM recipe_ingredients WHERE id in (".$ingredient_ids.")";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$this->returnResponse(SUCCESS, "Ingredient(s) Removed");
		}



		/* Chat Section */		

		public function ChatInboxList(){

			$this->userAccess(array('admin','staff'));


			$sql= "select 
							u.name,read_status,receive_status,ms.date_created,max(ms.id) as id,message,status_online,date_online_status,
							if(u.image!='',concat('". IMAGE_PATH ."',u.image) ,'') as image,
							ms.user_id
						FROM message_system as ms
						left join users as u on (u.id = ms.user_id) 
						left join users_token as ut on (u.id = ut.user_id) 
						group by ms.user_id
						order by max(ms.id) desc";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if(empty($chats)) {
				$this->returnResponse(BAD_REQUEST, "Chats not Found");
			}

			$this->returnResponse(SUCCESS, $chats);


		}	


		public function UnreadChats(){

			$this->userAccess(array('admin','staff'));


			$sql= "select 
							count(user_id),user_id
						FROM message_system
						where read_status = 0
						group by user_id
						order by max(id) desc";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if(empty($chats)) {
				$this->returnResponse(SUCCESS, array('unread_chats' => 0));
			}

			$this->returnResponse(SUCCESS, array('unread_chats' => count($chats)));


		}	


		public function UsersendMessages(){

			$this->userAccess(array('user'));

			$request = json_decode($this->request,true);
			$message = $this->validateParameter('message', $request['param']['message'] , STRING);

			$sql = "INSERT INTO message_system(sender_id,user_id,message, date_created) VALUES (". $this->userId .",". $this->userId .",'". $message ."',now())";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$tokens = array();
			$sql2= "select id,name,device_token from users where device_token!='' and user_type in('1','2')";
			$stmt2 = $this->dbConn->prepare($sql2);
			$stmt2->execute();
			$users = $stmt2->fetchAll(PDO::FETCH_ASSOC);
				
			if(!empty($users)){

				foreach($users as $user){

					$tokens[] = $user['device_token'];

				}

				if(!empty($tokens)){


					$this->sendFirebaseMessage($tokens,$message,NOTIFICATION_CHAT_TITLE,NOTIFICATION_CHAT_CLICK_ACTION);
				}

			}		



			$this->returnResponse(SUCCESS,"Message Sent");


		}

		public function sendMessages(){

			$this->userAccess(array('admin','staff'));

			$request = json_decode($this->request,true);
			$message = $this->validateParameter('message', $request['param']['message'] , STRING);
			$receiver_id = $this->validateParameter('receiver_id', $request['param']['receiver_id'] , INTEGER);

			$sql = "INSERT INTO message_system(sender_id,receiver_id,user_id, message, date_created) VALUES (". $this->userId .",". $receiver_id .",". $receiver_id .",'". $message ."',now())";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$tokens = array();
			$sql2= "select id,name,device_token from users where device_token!='' and id =".$receiver_id;
			$stmt2 = $this->dbConn->prepare($sql2);
			$stmt2->execute();
			$users = $stmt2->fetchAll(PDO::FETCH_ASSOC);
				
			if(!empty($users)){

				foreach($users as $user){

					$tokens[] = $user['device_token'];

				}

				if(!empty($tokens)){


					$this->sendFirebaseMessage($tokens,$message,NOTIFICATION_CHAT_TITLE,NOTIFICATION_CHAT_CLICK_ACTION);
				}

			}		



			$this->returnResponse(SUCCESS,"Message Sent");


		}


		public function MessageHistory(){

			$this->userAccess(array('admin','staff'));

			$request = json_decode($this->request,true);
			$receiver_id = $this->validateParameter('receiver_id', $request['param']['receiver_id'] , INTEGER);
			$sql= "select 
							ms.*,r.name as receiver_name,r.name as receiver_name ,
							s.name as sender_name
							from message_system as ms 
							left join users as r on (r.id = ms.receiver_id)
							left join users as s on (s.id = ms.sender_id)
							where user_id = ".$receiver_id." order by id asc";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if(empty($messages)){

				$this->returnResponse(BAD_REQUEST, "Message(s) not Found");

			}

			$this->returnResponse(SUCCESS, $messages);

		}



		public function UserMessageHistory(){

			$this->userAccess(array('user'));

			$request = json_decode($this->request,true);
			$sql= "select 
							ms.*,r.name as receiver_name,r.name as receiver_name ,
							s.name as sender_name
							from message_system as ms 
							left join users as r on (r.id = ms.receiver_id)
							left join users as s on (s.id = ms.sender_id)
							where user_id = ".$this->userId." order by id asc";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if(empty($messages)){

				$this->returnResponse(BAD_REQUEST, "Message(s) not Found");

			}

			$this->returnResponse(SUCCESS, $messages);

		}


		public function MessageReadStatusUpdate(){

			$this->userAccess(array('admin','staff','user'));

			$request = json_decode($this->request,true);
			$message_ids = $this->validateParameter('message_ids', $request['param']['message_ids'] , STRING);

			$sql = "update message_system set read_status = 1 where id in(".$message_ids .")";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$this->returnResponse(SUCCESS,"Message Read");


		}

		public function MessageReceiveStatusUpdate(){

			$this->userAccess(array('admin','staff','user'));

			$request = json_decode($this->request,true);
			$message_ids = $this->validateParameter('message_ids', $request['param']['message_ids'] , STRING);

			$sql = "update message_system set receive_status = 1 where id in(".$message_ids .")";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$this->returnResponse(SUCCESS,"Message Received");


		}


        //logs
        public function getUserBmi() {

		 	$this->userAccess(array('user','admin','staff'));
			$request = json_decode($this->request,true);
			$user_id = $this->validateParameter('user_id', $request['param']['user_id'] , INTEGER);

			$sql = "select id,bmi,date_format(date_log,'%d/%m/%Y') as date_log from user_logs where  FIND_IN_SET('BMI',changes) and bmi!='' and user_id =". $user_id ." order by id desc";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if(empty($data)) {
				$this->returnResponse(BAD_REQUEST, "Data not Found");
			}
				

			$this->returnResponse(SUCCESS, $data);


		}




		public function AddUserBmi() {

		 	$this->userAccess(array('user','admin','staff'));
			$request = json_decode($this->request,true);
			$user_id = $this->validateParameter('user_id', $request['param']['user_id'] , INTEGER);
			$bmi = $this->validateParameter('bmi', $request['param']['bmi'], STRING);


			$sql= "select id,bmi,date_created,date_updated from users where id =". $user_id;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$res = $stmt->fetch(PDO::FETCH_ASSOC);

			if(empty($res)) {
				$this->returnResponse(BAD_REQUEST, "Invaild User");
			}

			$old_bmi = $res['bmi'];
			$date_log = ($res['date_updated'] != '0000-00-00 00:00:00')?$res['date_updated']:$res['date_created'];


			$sql = "UPDATE users	
						SET 
							bmi='".$bmi."',
							old_bmi='".$old_bmi."',
							date_updated=now()
						WHERE id =". $user_id;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$sql = "INSERT INTO user_logs (user_id,bmi,changes,date_create,date_log) VALUES ('".$user_id."', '".$old_bmi."','BMI',now(),'". $date_log ."')";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$this->returnResponse(SUCCESS, "BMI Added");


		}

		public function EditUserBmi() {

		 	$this->userAccess(array('user','admin','staff'));

			$request = json_decode($this->request,true);

			$bmi = $this->validateParameter('bmi', $request['param']['bmi'], STRING);
			$id = $this->validateParameter('id', $request['param']['id'] , INTEGER);

			$sql = "UPDATE user_logs	
						SET 
							bmi='".$bmi."'
						WHERE id =". $id;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

		
		 $this->returnResponse(SUCCESS, "BMI Updated");


		}


		public function getUserWeight() {

		 	$this->userAccess(array('user','admin','staff'));
			$request = json_decode($this->request,true);
			$user_id = $this->validateParameter('user_id', $request['param']['user_id'] , INTEGER);


			$sql = "select id,weight,date_format(date_log,'%d/%m/%Y') as date_log from user_logs where  FIND_IN_SET('Weight',changes) and weight > 0 and user_id =". $user_id." order by id desc";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if(empty($data)) {
				$this->returnResponse(BAD_REQUEST, "Data not Found");
			}
				

			$this->returnResponse(SUCCESS, $data);


		}



		public function AddUserWeight() {

		 	$this->userAccess(array('user','admin','staff'));
			$request = json_decode($this->request,true);
			$user_id = $this->validateParameter('user_id', $request['param']['user_id'] , INTEGER);
			$weight = $this->validateParameter('weight', $request['param']['weight'], STRING,false);

			$sql= "select id,weight,date_created,date_updated from users where id =". $user_id;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$res = $stmt->fetch(PDO::FETCH_ASSOC);

			if(empty($res)) {
				$this->returnResponse(BAD_REQUEST, "Invaild User");
			}

			$old_weight = $res['weight'];
			$date_log = ($res['date_updated'] != '0000-00-00 00:00:00')?$res['date_updated']:$res['date_created'];



			$sql = "UPDATE users	
						SET 
							weight='".$weight."',
							old_weight='".$old_weight."',
							date_updated=now()
						WHERE id =". $user_id;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$sql = "INSERT INTO user_logs (user_id,weight,changes,date_create,date_log) VALUES ('".$user_id."', '".$old_weight."','Weight',now(),'". $date_log ."')";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$this->returnResponse(SUCCESS, "Weight Added");

		}



		public function EditUserWeight() {

		 	$this->userAccess(array('user','admin','staff'));

			$request = json_decode($this->request,true);

			$weight = $this->validateParameter('weight', $request['param']['weight'], STRING,false);
			$id = $this->validateParameter('id', $request['param']['id'] , INTEGER);

			$sql = "UPDATE user_logs	
						SET 
							weight='".$weight."'
						WHERE id =". $id;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

		
		 $this->returnResponse(SUCCESS, "Weight Updated");


		}


		public function DeleteUserLog() {

		 	$this->userAccess(array('user','admin','staff'));

			$request = json_decode($this->request,true);

			$id = $this->validateParameter('id', $request['param']['id'] , INTEGER);

			$sql = "delete from user_logs WHERE id =". $id;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$this->returnResponse(SUCCESS, "Log Deleted");

		}


		/* before/after */


		public function getBeforeAfterImages() {

		 	$this->userAccess(array('user','admin','staff'));
			$request = json_decode($this->request,true);
			$user_id = $this->validateParameter('user_id', $request['param']['user_id'] , INTEGER);


			$sql = "select 
							*,
							if(before_image!='',concat('". IMAGE_PATH ."',before_image) ,'') as before_image, 
							if(after_image!='',concat('". IMAGE_PATH ."',after_image) ,'') as after_image
						from patient_before_after_images where user_id =". $user_id." order by id desc";
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
			$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if(empty($data)) {
				$this->returnResponse(BAD_REQUEST, "Data not Found");
			}
				

			$this->returnResponse(SUCCESS, $data);


		}


		public function DeleteBeforeAfterImage() {

		 	$this->userAccess(array('user','admin','staff'));

			$request = json_decode($this->request,true);

			$id = $this->validateParameter('id', $request['param']['id'] , INTEGER);


			$sql= "select before_image,after_image FROM patient_before_after_images WHERE id =".$id;
		      
		    $stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
		    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
		    if(!empty($images)) {
		        foreach($images as $key => $image){
		        	if($image['before_image']!=''){
			            if (file_exists("images/".$image['before_image'])){
			               unlink("images/".$image['before_image']);
			            }
			        } 


			        if($image['after_image']!=''){
			            if (file_exists("images/".$image['after_image'])){
			               unlink("images/".$image['after_image']);
			            }
			        }   
		        }
		    }



			$sql = "delete from patient_before_after_images WHERE id =". $id;
			$stmt = $this->dbConn->prepare($sql);
			$stmt->execute();



			$this->returnResponse(SUCCESS, "Log Deleted");

		}

		public function removeBeforeImage() {

		 	$this->userAccess(array('user','admin','staff'));

			$request = json_decode($this->request,true);

			$id = $this->validateParameter('id', $request['param']['id'] , INTEGER);	

			$sql= "select before_image FROM patient_before_after_images WHERE id =".$id;
		      
		    $stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
		    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
		    if(!empty($images)) {
		        foreach($images as $key => $image){
		        	if($image['before_image']!=''){
			            if (file_exists("images/".$image['before_image'])){
			               unlink("images/".$image['before_image']);
			            }
			        }    
		        }
		    }



			$sql= "UPDATE patient_before_after_images SET before_image = '', date_update_before = now() WHERE id =".$id;
		    $stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$this->returnResponse(SUCCESS, "Image removed");

		}


		public function removeAfterImage() {

		 	$this->userAccess(array('user','admin','staff'));

			$request = json_decode($this->request,true);

			$id = $this->validateParameter('id', $request['param']['id'] , INTEGER);	

			$sql= "select after_image FROM patient_before_after_images WHERE id =".$id;
		      
		    $stmt = $this->dbConn->prepare($sql);
			$stmt->execute();
		    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
		    if(!empty($images)) {
		        foreach($images as $key => $image){
		        	if($image['after_image']!=''){
			            if (file_exists("images/".$image['after_image'])){
			               unlink("images/".$image['after_image']);
			            }
			        }    
		        }
		    }



			$sql= "UPDATE patient_before_after_images SET after_image = '', date_update_after = now() WHERE id =".$id;
		    $stmt = $this->dbConn->prepare($sql);
			$stmt->execute();

			$this->returnResponse(SUCCESS, "Image removed");

		}



	}	

	
 ?>