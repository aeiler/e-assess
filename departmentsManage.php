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
	
	
	// Retrieve Current List of Departments
	$stmt = $pdo->prepare("SELECT `ID`, `name`, `status` FROM `Department` ORDER BY `status` DESC, `name`");
	$success = $stmt->execute();
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Manage Departments</title>
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
				<h2 class="title">Manage Departments</h2>
				
				<table class="verticalTable padded widthFull">
					<tr>
						<th>Department Name</th>
						<th>Edit</th>
						<th>Disable/Enable</th>
					</tr>
					
					<?php
						while ($success && ($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== FALSE)
						{
					?>
					<tr>
						<td><?php echo $row['name']; ?></td>
						<td class="center">
							<a href="departmentEdit.php?idDepartment=<?php echo $row['ID']; ?>">
								<img src="media/edit.png" title="Edit Department <?php echo $row['name']; ?>" width="30" />
							</a>
						</td>
						<td class="center">
							<?php
								if ($row['ID'] != $_SESSION['fkDepartment'])
								{
									echo '<a href="departmentStatus.php?idDepartment=' . $row['ID'] . '">';
									echo ($row['status']) ? '<img src="media/plus.png" title="Disable ' . $row['name'] . '" width="30" />' : '<img src="media/minus.png" title="Enable ' . $row['name'] . '" width="30" />';
									echo '</a>';
								}
								else echo '-';
							?>
						</td>
					</tr>
					<?php
						}
					?>
					
				</table>
				
				<a href="departmentNew.php" class="button">Create a New Department</a>
			</section>
		</div>
		
		<?php require('html/footer.php'); ?>
	</body>
</html>