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
	
	
	// Retrieve Current List of Degrees
	$query = "
		SELECT `ID`, `name`, `code`, `status`
		FROM `Degree`
		WHERE `fkDepartment` = :fkDepartment
		ORDER BY `status` DESC, `name`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Manage Degrees</title>
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
				<h2 class="title">Manage Degrees</h2>
					
				<table class="verticalTable padded widthFull">
					<tr>
						<th>Name</th>
						<th>Code</th>
						<th>Edit</th>
						<th>Disable/Enable</th>
					</tr>
					
					<?php
						while ($success && ($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== FALSE)
						{
					?>
					<tr>
						<td><?php echo $row['name']; ?></td>
						<td class="center"><?php echo $row['code']; ?></td>
						<td class="center">
							<a href="degreeEdit.php?idDegree=<?php echo $row['ID']; ?>">
								<img src="media/edit.png" title="Edit Degree <?php echo $row['code']; ?>" width="30" />
							</a>
						</td>
						<td class="center">
							<a href="degreeStatus.php?idDegree=<?php echo $row['ID']; ?>">
								<?php echo ($row['status']) ? '<img src="media/plus.png" title="Disable ' . $row['code'] . '" width="30" />' : '<img src="media/minus.png" title="Enable ' . $row['code'] . '" width="30" />'; ?>
							</a>
						</td>
					</tr>
					<?php
						}
					?>
					
				</table>
				
				<a href="degreeNew.php" class="button">Create a New Degree</a>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>