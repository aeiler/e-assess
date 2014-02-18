<?php
	
	// Include Utilities PHP File
	require_once('_utilities.php');
	
	
	// Initialize Error Array
	$errors = array();
	
	
	// Start Session
	session_start();
	
	
	// Connect To Database
	$pdo = dbConnect();
	
	
	// Require Login
	requireLogin();
	
	
	// Retrieve Current List of Degrees
	$query = "
		SELECT `ID`, `name`, `code`
		FROM `Degree`
		WHERE `status` = 1 AND `fkDepartment` = :fkDepartment
		ORDER BY `name`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - View Degrees</title>
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
				<h2 class="title">View Degrees</h2>
					
				<table class="verticalTable padded widthFull">
					<tr>
						<th>Name</th>
						<th>Code</th>
					</tr>
					
					<?php
						while ($success && ($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== FALSE)
						{
					?>
					<tr>
						<td><?php echo $row['name']; ?></td>
						<td><?php echo $row['code']; ?></td>
					</tr>
					<?php
						}
					?>
					
				</table>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>