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
	
	
	// Retrieve Current List of Users
	$query = "
		SELECT
			`firstName`,
			`lastName`,
			`username`,
			`level`
		FROM `User`
		WHERE `status` = 1 AND `fkDepartment` = :fkDepartment
		ORDER BY `lastName`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - View Users</title>
		<link rel="icon" href="media/favicon.ico" />
		
		<!-- Meta Information -->
		<meta charset="utf-8" />
		
		<!-- Stylesheets -->
		<link rel="stylesheet" type="text/css" href="css/_reset.css" />
		<link rel="stylesheet" type="text/css" href="css/_globalStyles.css" />

	<body>
		<?php require('html/header.php'); ?>
		
		<div id="siteContainer" class="pageWidth">
			<section class="bgField shadow corner center">
				<h2 class="title">View Users</h2>
				
				<table class="verticalTable padded widthFull">
					<tr>
						<th>Full Name</th>
						<th>Username</th>
						<th>Level</th>
					</tr>
					
					<?php
						while ($success && ($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== FALSE)
						{
					?>
					<tr>
						<td><?php echo $row['lastName'] . ', ' . $row['firstName']; ?></td>
						<td class="center"><?php echo $row['username']; ?></td>
						<td class="center">
							<?php
								switch ($row['level'])
								{
									case LEVEL_FACULTY:
										echo 'Faculty';
										break;
									case LEVEL_ADMIN:
										echo 'Department Admin';
										break;
									case LEVEL_SU:
										echo 'System Admin';
										break;
								}
							?>
						</td>
					</tr>
					<?php
						}
					?>
					
				</table>
			</div>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>