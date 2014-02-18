<?php
	
	// Include Utilities PHP File
	require_once('_utilities.php');
	
	
	// Initialize Error Array
	$errors = array();
	
	
	// Start Session
	session_start();
	
	
	// Connect To Database
	$pdo = dbConnect();
	
	
	// Require Admin or Super User Login
	requireLogin(LEVEL_ADMIN, LEVEL_SU);
	
	
	// Require Provided User ID
	// 1. Attempt to Query Database for Supplied User's Info
	$query = "
		SELECT
			`User`.`ID` AS ID,
			`User`.`fkDepartment`,
			`User`.`firstName`,
			`User`.`lastName`,
			`User`.`username`,
			`User`.`level`
		FROM `User`
		JOIN `Department`
		ON `User`.`fkDepartment` = `Department`.`ID`
		WHERE `User`.`ID` = :ID AND `User`.`status` = 1 AND `Department`.`status` = 1
		LIMIT 1";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':ID', parseInt($_REQUEST['idUser']));
	$success = $stmt->execute();
	
	// 2. Ensure a Valid User Was Found in the Database
	if (!$success || ($userInfo = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE) redirect('usersManage.php');
	
	// 3. If Logged In User Is Admin, Check Department
	if ($_SESSION['level'] != LEVEL_SU && $_SESSION['fkDepartment'] != $userInfo['fkDepartment'])
		redirect('usersManage.php');
	
	
	// Handle Cancel Button
	if (isset($_POST['pswdResetCancel']) && $_POST['pswdResetCancel']) redirect('usersManage.php');
	
	// Handle Submit Button
	if (isset($_POST['pswdResetSubmit']) && $_POST['pswdResetSubmit'])
	{
		// Validate New Passwords
		// 1. Check Presence of Passwords
		if (!isset($_POST['password1']) || !isset($_POST['password2']))
			$errors[] = "Password is a required field.";
		
		// 2. Check Passwords for Match
		else if (strcmp($_POST['password1'], $_POST['password2']) != 0)
			$errors[] = "Passwords do not match.";
		
		// 3. Check Password for Security
		else if (strlen($_POST['password1']) < 8 || preg_match('#\d#', $_POST['password1']) != 1)
			$errors[] = "Invalid password. Password must be at least 8 characters long and contain at least one digit.";
		
		
		// Submit User Changes to Database
		if (count($errors) == 0)
		{
			// 2. Send Query
			$stmt = $pdo->prepare("UPDATE `User` SET `passwordHash` = :passwordHash WHERE `ID` = :ID");
			$stmt->bindValue(':passwordHash', hasher(parseStr($_POST['password1'])));
			$stmt->bindValue(':ID', $userInfo['ID']);
			$success = $stmt->execute();
			
			
			// 3. Check for Errors
			if (!$success) $errors[] = "Unknown database error occurred.";
			
			// 4. Success - Refresh Page To Reflect Edits
			else redirect('usersManage.php');
		}
	}
	
	
	// Populate Form Data With Correct Values
	$PAGEDATA = array();
	$PAGEDATA['idUser'] = $userInfo['ID'];
	$PAGEDATA['username'] = $userInfo['username'];
	$PAGEDATA['password1'] = parseStr($_POST['password1']);
	
?>



<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Password Reset Tool</title>
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
			<section id="pswdReset" class="bgField shadow corner center">
				<h2 class="title">Reset Password for User <?php echo $PAGEDATA['username']; ?></h2>
				
				<?php outputErrorsHTML($errors); ?>
					
				<form name="pswdReset" action="" method="POST">
					<table class="formTable padded widthFull">
						<tr>
							<th>New Password</th>
							<td><input name="password1" type="password" value="<?php echo $PAGEDATA['password1']; ?>" autofocus /></td>
						</tr>
						<tr>
							<th>Confirm New Password</th>
							<td><input name="password2" type="password" /></td>
						</tr>
						<tr>
							<td colspan="2">
								<div>*Password must be at least 8 characters with at least one digit.</div>
								<input name="pswdResetSubmit" type="submit" value="Update" />
								<input name="pswdResetCancel" type="submit" value="Cancel" />
							</td>
						</tr>
					</table>
					
					<input name="idUser" type="hidden" value="<?php echo $PAGEDATA['idUser']; ?>" />
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>