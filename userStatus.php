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
			`User`.`level`,
			`User`.`status`
		FROM `User`
		JOIN `Department`
		ON `User`.`fkDepartment` = `Department`.`ID`
		WHERE `User`.`ID` = :ID AND `Department`.`status` = 1
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
	if (isset($_POST['statusUserCancel']) && $_POST['statusUserCancel']) redirect('usersManage.php');
	
	// Handle Submit Button
	if (isset($_POST['statusUserSubmit']) && $_POST['statusUserSubmit'])
	{
		// 1. Send Query
		$stmt = $pdo->prepare("UPDATE `User` SET `status` = ABS(`status` - 1) WHERE `ID` = :ID");
		$stmt->bindValue(':ID', $userInfo['ID']);
		$success = $stmt->execute($params);
		
		// 2. Check for Errors
		if (!$success) $errors[] = "Unknown database error occurred.";
		
		// 3. On Success, Redirect Page
		else redirect('usersManage.php');
	}
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - <?php echo ($userInfo['status']) ? 'Disable' : 'Enable'; ?> User</title>
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
			<section class="bgField shadow corner center">
				<h2 class="title"><?php echo ($userInfo['status']) ? 'Disable' : 'Enable'; ?> User</h2>
				
				<?php outputErrorsHTML($errors); ?>
				
				<p>Are you sure you want to <?php echo ($userInfo['status']) ? 'disable' : 'enable'; ?> user <?php echo $userInfo['username']; ?>?</p>
				
				<form name="statusUser" action="" method="POST">
					<table class="formTable padded widthFull">
						<tr>
							<td colspan="2"><input name="statusUserSubmit" type="submit" value="<?php echo ($userInfo['status']) ? 'Disable' : 'Enable'; ?>" /><input name="statusUserCancel" type="submit" value="Cancel" /></td>
						</tr>
					</table>
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>