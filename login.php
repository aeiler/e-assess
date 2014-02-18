<?php
	
	// Include Utilities PHP File
	require_once('_utilities.php');
	
	
	// Initialize Error Array
	$errors = array();
	
	
	// Start Session
	session_start();
	
	
	// Handle Logout
	if (filter_var(parseStr($_REQUEST['logout']), FILTER_VALIDATE_BOOLEAN))
	{
		logOut();
		if (isblank($_REQUEST['msg']))
		{
			redirect();
		}
		else
		{
			redirect('', array('msg' => $_REQUEST['msg']));
		}
	}
	
	
	// Connect To Database
	$pdo = dbConnect();
	
	
	// Handle Form Submit Button
	if (isset($_POST['loginUserSubmit']) && $_POST['loginUserSubmit'])
	{
		// Validate Given Username and Password
		// 1. Trim Username, Password
		$username = strtolower(parseStr($_POST['username']));
		$password = parseStr($_POST['password']);
		
		// 2. Validate Username
		if (strlen($username) < 2 || strlen($username) > 10)
		{
			$errors[] = "Invalid username. Username must be at least 2 characters and no more than 10 characters.";
		}
		
		// 3. Validate Password
		if (strlen($password) < 8 || preg_match('#\d#', $password) != 1)
		{
			$errors[] = "Invalid password. Password must be at least 8 characters long and contain at least one digit.";
		}
		
		
		// Attempt User Login
		if (count($errors) == 0)
		{
			// 1. Retrieve User Information From Database
			$query = "
				SELECT
					`User`.`ID`,
					`User`.`fkDepartment`,
					`User`.`firstName`,
					`User`.`lastName`,
					`User`.`username`,
					`User`.`passwordHash`,
					`User`.`level`,
					`User`.`status` AS userStatus,
					`Department`.`status` AS deptStatus
				FROM `User`
				LEFT JOIN `Department`
				ON `Department`.`ID` = `User`.`fkDepartment`
				WHERE
					`User`.`username` = :username
				LIMIT 1
			";
			$stmt = $pdo->prepare($query);
			$stmt->bindValue(':username', $username);
			$success = $stmt->execute();
			
			// 2. Username/Password Pair Found
			if ($success && ($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== FALSE && hasher($password, $row['passwordHash']))
			{
				// 2.1. Check Department Enabled
				if (intval($row['deptStatus']) === 0)
				{
					$errors[] = "Your department has been disabled. If you believe that this is in error, please contact the system administrator.";
				}
				
				// 2.2. Check User Enabled
				elseif (intval($row['userStatus']) === 0)
				{
					$errors[] = "Your account has been disabled or deleted. If you believe that this is in error, please contact the system administrator.";
				}
				
				// 2.3. Success
				else
				{
					// 2.3.1. End Previous Session (If Any)
					logOut();
					
					// 2.3.2. Start New Session
					session_start();
					$_SESSION['ID'] = $row['ID'];
					$_SESSION['fkDepartment'] = $row['fkDepartment'];
					$_SESSION['firstName'] = $row['firstName'];
					$_SESSION['lastName'] = $row['lastName'];
					$_SESSION['username'] = $row['username'];
					$_SESSION['level'] = $row['level'];
					
					// 2.3.3. Redirect User
					if (isblank($_REQUEST['source']))
					{
						redirect('index.php');
					}
					else
					{
						redirect(parseStr($_REQUEST['source']));
					}
				}
			}
			
			// 3. Unsuccessful Login - Invalid Username/Password Combination
			else
			{
				$errors[] = "Incorrect username or password.";
			}
		}
	}
	
	
	// Populate Form Data With Correct Values
	$PAGEDATA = array();
	$PAGEDATA['username'] = parseStr($_POST['username']);
	$PAGEDATA['source'] = parseStr($_REQUEST['source']);
	$PAGEDATA['msg'] = parseStr($_REQUEST['msg']);
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Login</title>
		<link rel="icon" href="media/favicon.ico" />
		
		<!-- Meta Information -->
		<meta charset="utf-8" />
		
		<!-- Stylesheets -->
		<link rel="stylesheet" type="text/css" href="css/_reset.css" />
		<link rel="stylesheet" type="text/css" href="css/_globalStyles.css" />
	</head>
	
	<body>
		<?php require('html/header.php'); ?>
		
		<div id="siteContainer" class="pageWidth">
			
			<section id="loginBox" class="bgField shadow corner center">
				<h2>Welcome</h2>
				<h3>
					<?php
						if (!isblank($_REQUEST['msg']))
						{
							echo parseStr($_REQUEST['msg']);
						}
						else
						{
							echo "Please login below.";
						}
					?>
				</h3>
				
				<?php outputErrorsHTML($errors); ?>
				
				<form name="loginUser" action="" method="POST" >
					<table class="formTable padded widthFull">
						<tr>
							<th>Username</th>
							<td><input type="text" name="username" autocomplete="off" value="<?php echo $PAGEDATA['username']; ?>" autofocus /></td>
						</tr>
						<tr>
							<th>Password</th>
							<td><input type="password" name="password" /></td>
						</tr>
						<tr>
							<td colspan="2"><input type="submit" name="loginUserSubmit" value="Login" /></td>
						</tr>
					</table>
					
					<input type="hidden" name="source" value="<?php echo $PAGEDATA['source']; ?>" />
					<input type="hidden" name="msg" value="<?php echo $PAGEDATA['msg']; ?>" />
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>