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
	
	
	// Require Provided SLO ID
	$query = "
		SELECT DISTINCT
			`SLO`.`ID` AS ID,
			`SLO`.`code`,
			`SLO`.`description`,
			`SLO`.`status`
		FROM `SLO`
		JOIN `SLOXDegree`
		ON `SLOXDegree`.`fkSLO` = `SLO`.`ID`
		JOIN `Degree`
		ON `Degree`.`ID` = `SLOXDegree`.`fkDegree`
		WHERE `SLO`.`ID` = :ID AND `Degree`.`fkDepartment` = :fkDepartment
		LIMIT 1
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':ID', parseInt($_REQUEST['idSLO']));
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	if (!$success || ($SLOInfo = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('SLOsManage.php');
	}
	
	
	// Handle Cancel Button
	if (isset($_POST['statusSLOCancel']) && $_POST['statusSLOCancel']) redirect('SLOsManage.php');
	
	// Handle Submit Button
	if (isset($_POST['statusSLOSubmit']) && $_POST['statusSLOSubmit'])
	{
		// 1. Send Query
		$stmt = $pdo->prepare("UPDATE `SLO` SET `status` = ABS(`status` - 1) WHERE `ID` = :ID");
		$stmt->bindValue(':ID', $SLOInfo['ID']);
		$success = $stmt->execute($params);
		
		// 2. Check for Errors
		if (!$success) $errors[] = "Unknown database error occurred.";
		
		// 3. On Success, Redirect Page
		else redirect('SLOsManage.php');
	}
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - <?php echo (intval($SLOInfo['status'])) ? 'Disable' : 'Enable'; ?> SLO</title>
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
				<h2 class="title"><?php echo (intval($SLOInfo['status'])) ? 'Disable' : 'Enable'; ?> SLO</h2>
				
				<?php outputErrorsHTML($errors); ?>
				
				<p>Are you sure you want to <?php echo (intval($SLOInfo['status'])) ? 'disable' : 'enable'; ?> SLO "<?php echo $SLOInfo['code']; ?>"?</p>
				
				<form name="statusSLO" action="" method="POST">
					<table class="formTable padded widthFull">
						<tr>
							<td colspan="2"><input name="statusSLOSubmit" type="submit" value="<?php echo (intval($SLOInfo['status'])) ? 'Disable' : 'Enable'; ?>" /><input name="statusSLOCancel" type="submit" value="Cancel" /></td>
						</tr>
					</table>
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>