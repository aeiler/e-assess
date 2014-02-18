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
	
	
	// Retrieve Current List of Users
	$query = "
		SELECT
			`User`.`ID` AS ID,
			`User`.`firstName`,
			`User`.`lastName`,
			`User`.`username`,
			`User`.`level`,
			`User`.`status` AS status,
			`User`.`fkDepartment`,
			`Department`.`name`
		FROM `User`
		JOIN `Department`
		ON `Department`.`ID` = `User`.`fkDepartment`
		WHERE
			`Department`.`status` = 1 AND
			`User`.`level` <= :level
		ORDER BY `Department`.`name`, `User`.`status` DESC, `User`.`lastName`";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':level', $_SESSION['level']);
	$success = $stmt->execute();
	if (!$success || ($users = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE) redirect('index.php');
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Manage Users</title>
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
			<section id="manageUsers" class="bgField shadow corner center">
				<h2 class="title">Manage Users</h2>
				
				<?php
					// Create A Seperate Table For Each Department
					$dept = -1;
					foreach ($users as $user)
					{
						// If This is a New Department, Create A New Table
						if ($dept != intval($user['fkDepartment']))
						{
							// End the Previous Department's Table (Unless This is the First Table)
							if ($dept != -1)
							{
								echo '</table>' . "\n";
								echo '<a href="userNew.php" class="button">Create a New User</a>' . "\n";
							}
							$dept = intval($user['fkDepartment']);
				?>
				
				<h3><?php echo $user['name']; ?></h3>
				
				<table class="verticalTable padded widthFull">
					<tr>
						<th>Full Name</th>
						<th>Username</th>
						<th>Level</th>
						<th>Manage Coordinators</th>
						<th>Password Reset</th>
						<th>Disable/Enable</th>
					</tr>
					
					<?php
						}
					?>
					
					<tr>
						<td><?php echo $user['lastName'] . ', ' . $user['firstName']; ?></td>
						<td class="center"><?php echo $user['username']; ?></td>
						<td class="center">
							<?php
								switch ($user['level'])
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
						<td class="center">
							<?php
								if ($user['status'])
								{
									echo '<a href="coordinatorEditUser.php?idUser=' . $user['ID'] . '">';
									echo '<img src="media/manage.png" title="Manage Classes for ' . $user['username'] . '" width="30" />';
									echo '</a>';
								}
								else echo '-';
							?>
						</td>
						<td class="center">
							<?php
								if ($user['status'] && ($_SESSION['level'] == LEVEL_SU || $user['level'] == LEVEL_FACULTY))
								{
									echo '<a href="adminPswdReset.php?idUser=' . $user['ID'] . '">';
									echo '<img src="media/key.png" title="Reset Password for ' . $user['username'] . '" width="30" />';
									echo '</a>';
								}
								else echo '-';
							?>
						</td>
						<td class="center">
							<?php
								if ($user['ID'] != $_SESSION['ID'] && ($_SESSION['level'] == LEVEL_SU || $user['level'] == LEVEL_FACULTY))
								{
									echo '<a href="userStatus.php?idUser=' . $user['ID'] . '">';
									echo ($user['status']) ? '<img src="media/plus.png" title="Disable ' . $user['username'] . '" width="30" />' : '<img src="media/minus.png" title="Enable ' . $user['username'] . '" width="30" />';
									echo '</a>';
								}
								else echo '-';
							?>
						</td>
					</tr>
					
					<?php
						}
						unset($user);
					?>
					
				</table>
				
				<a href="userNew.php" class="button">Create a New User</a>
			</section>
		</div>
		
		<?php require('html/footer.php'); ?>
	</body>
</html>