<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title></title>
</head>
<body>

<?php
	$msg = "";
	$err=0;
	if(isset($_GET['auth']) && $_GET['auth']!=''){
		
    	include("../dbconnect.php");
    	include("../constants.php");

    	$db = new DbConnect;
    	$dbConn = $db->connect();

		$sql="SELECT id,name,user_blocked,TIMESTAMPDIFF(DAY,forgot_password_otp_date,now()) as count_time FROM users where md5(concat(id,forgot_password_otp)) ='".$_GET['auth']."'";
		$stmt = $dbConn->prepare($sql);
	    $stmt->execute();
	   	$res = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!empty($res)) {

			if($res['user_blocked']==1){

				$msg = "User is not activated. Please contact to admin.";
				$err=1;
			}

			if($res['count_time']>FORGOT_PASSWORD_VALIDITY){

				$msg = "Link Expired!";
				$err=1;
			}
		   	
		   	
			if(isset($_POST['submit'])){
			
				$pass = isset($_POST['pass'])?$_POST['pass']:'';
				$cpass = isset($_POST['cpass'])?$_POST['cpass']:'';

				if($pass==''){

					$msg = "Password is empty";
					$err=2;

				}

				if($cpass==''){

					$msg = "Confirm password is empty";
					$err=2;
				}


				if($pass!=$cpass){
					
					$msg = "Password and Confirm password not match";
					$err=2;

				}

			    $password = md5(md5($pass)); 

				if($err==0){
					$user_id=$res['id'];
					$sql= "UPDATE users SET password = '". $password ."' , forgot_password_otp ='' WHERE id ='". $user_id ."'";
					$stmt = $dbConn->prepare($sql);
				    $stmt->execute();
				    header("Location: passwordchanges.php");

				}

			}	

		}else{
			$err=1;
			$msg = "You don't have access to this service.";
		}

	}else{
		$err=1;
		$msg = "You don't have access to this service.";
	}


?>

<?php if($err==0 || $err==2){ ?>

	<p>Reset Password</p>
	<form method="POST">
		<p>Password</p>
		<input type="password" name="pass" required>
		<p>Confirm Password</p>
		<input type="password" name="cpass" required><br>
		<input type="submit" name="submit">
	</form>	

<?php } ?>


<?php if($msg!=''){ ?>
	<p><?php echo $msg; ?></p>
<?php } ?>	




</body>
</html>

