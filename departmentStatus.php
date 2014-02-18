<?php
	
	// Include Utilities PHP File
	require_once('_utilities.php');
	
	
	// Initialize Error Array
	$errors = array();
	
	
	// Start Session
	session_start();
	
	
	// Connect To Database
	$pdo = dbConnect();
	
	
	// Require Super User Login
	requireLogin(LEVEL_SU);
	
	
	
	
	// Require Provided Department ID
	// 1. Attempt to Query Database for Department Info
	$stmt = $pdo->prepare("SELECT `ID`, `name`, `status` FROM `Department` WHERE `ID` = :ID LIMIT 1");
	$stmt->bindValue(':ID', parseInt($_REQUEST['idDepartment']));
	$success = $stmt->execute();
	
	// 2. Ensure a Valid Department Was Found in the Database
	if (!$success || ($deptInfo = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE) redirect('departmentsManage.php');
	
	// 3. Check That Department is Not User's Department
	if ($deptInfo['ID'] == $_SESSION['fkDepartment']) redirect('departmentsManage.php');
	
	
	
	
	// Handle Cancel Button
	if (isset($_POST['statusDepartmentCancel']) && $_POST['statusDepartmentCancel']) redirect('departmentsManage.php');
	
	// Handle Submit Button
	if (isset($_POST['statusDepartmentSubmit']) && $_POST['statusDepartmentSubmit'])
	{
		// 1. Send Query
		$stmt = $pdo->prepare("UPDATE `Department` SET `status` = ABS(`status` - 1) WHERE `ID` = :ID");
		$stmt->bindValue(':ID', $deptInfo['ID']);
		$success = $stmt->execute($params);
		
		// 2. Check for Errors
		if (!$success) $errors[] = "Unknown database error occurred.";
		
		// 3. On Success, Redirect Page
		else redirect('departmentsManage.php');
	}
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - <?php echo ($deptInfo['status']) ? 'Disable' : 'Enable'; ?> Department</title>
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
				<h2 class="title"><?php echo ($deptInfo['status']) ? 'Disable' : 'Enable'; ?> Department</h2>
				
				<?php outputErrorsHTML($errors); ?>
				
				<p>Are you sure you want to <?php echo ($deptInfo['status']) ? 'disable' : 'enable'; ?> department "<?php echo $deptInfo['name']; ?>"?</p>
				
				<form name="statusDepartment" action="" method="POST">
					<table class="formTable padded widthFull">
						<tr>
							<td colspan="2"><input name="statusDepartmentSubmit" type="submit" value="<?php echo ($deptInfo['status']) ? 'Disable' : 'Enable'; ?>" /><input name="statusDepartmentCancel" type="submit" value="Cancel" /></td>
						</tr>
					</table>
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>