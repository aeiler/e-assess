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
	
	
	// Require Provided Degree ID
	// 1. Attempt to Query Database for Supplied Degree's Info
	$query = "
		SELECT `ID`, `name`, `status`
		FROM `Degree`
		WHERE `Degree`.`ID` = :ID AND `Degree`.`fkDepartment` = :fkDepartment
		LIMIT 1
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':ID', intval($_REQUEST['idDegree']));
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	
	// 2. Ensure a Valid Degree Was Found in the Database
	if (!$success || ($degreeInfo = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE) redirect('degreesManage.php');
	
	
	// Handle Cancel Button
	if (isset($_POST['statusDegreeCancel']) && $_POST['statusDegreeCancel']) redirect('degreesManage.php');
	
	// Handle Submit Button
	if (isset($_POST['statusDegreeSubmit']) && $_POST['statusDegreeSubmit'])
	{
		// 1. Send Query
		$stmt = $pdo->prepare("UPDATE `Degree` SET `status` = ABS(`status` - 1) WHERE `ID` = :ID");
		$stmt->bindValue(':ID', $degreeInfo['ID']);
		$success = $stmt->execute($params);
		
		// 2. Check for Errors
		if (!$success) $errors[] = "Unknown database error occurred.";
		
		// 3. On Success, Redirect Page
		else redirect('degreesManage.php');
	}
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - <?php echo (intval($degreeInfo['status'])) ? 'Disable' : 'Enable'; ?> Degree</title>
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
				<h2 class="title"><?php echo (intval($degreeInfo['status'])) ? 'Disable' : 'Enable'; ?> Degree</h2>
				
				<?php outputErrorsHTML($errors); ?>
				
				<p>Are you sure you want to <?php echo (intval($degreeInfo['status'])) ? 'disable' : 'enable'; ?> degree "<?php echo $degreeInfo['name']; ?>"?</p>
				
				<form name="statusDegree" action="" method="POST">
					<table class="formTable padded widthFull">
						<tr>
							<td colspan="2"><input name="statusDegreeSubmit" type="submit" value="<?php echo (intval($degreeInfo['status'])) ? 'Disable' : 'Enable'; ?>" /><input name="statusDegreeCancel" type="submit" value="Cancel" /></td>
						</tr>
					</table>
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>