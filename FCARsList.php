<?php
	
	// Define Constants
	define('RESULTS_PER_PAGE', 15);
	define('PAGING_RADIUS', 2);
	
	
	// Include Utilities PHP File
	require_once('_utilities.php');
	require_once('_paging.function.php');
	
	
	// Initialize Error Array
	$errors = array();
	
	
	// Start Session
	session_start();
	
	
	// Connect To Database
	$pdo = dbConnect();
	
	
	// Require Admin or Super User Login
	requireLogin(LEVEL_ADMIN, LEVEL_SU);
	
	
	
	
	// Retrieve Total Number of Submitted (Closed) FCARs
	$query = "
		SELECT COUNT(*) AS num
		FROM `FCAR`
		JOIN `User`
		ON `User`.`ID` = `FCAR`.`fkUser`
		WHERE
			`User`.`fkDepartment` = :fkDepartment AND
			`FCAR`.`status` = 1 AND
			`FCAR`.`dateSubmitted` IS NOT NULL
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$stmt->execute();
	if (($row = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('index.php');
	}
	else
	{
		$numItems = $row['num'];
	}
	
	
	
	
	// Retrieve List of Submitted (Closed) FCARs
	// 1. Prepare Paging
	$page = parseInt($_GET['page'], 0);
	$lastPage = max(ceil($numItems/RESULTS_PER_PAGE)-1, 0);
	$page = min($page, $lastPage);
	$limit = $page*RESULTS_PER_PAGE . ", " . RESULTS_PER_PAGE;
	
	// 2. Prepare Sorting
	switch (parseStr($_GET['sort']))
	{
		case "user":
			$sort = "
				`User`.`lastName`,
				`User`.`firstName`,
				`FCAR`.`year` DESC,
				`Course`.`prefix`,
				`Course`.`number`
			";
			break;
			
		case "course":
			$sort = "
				`Course`.`prefix`,
				`Course`.`number`,
				`User`.`lastName`,
				`User`.`firstName`,
				`FCAR`.`year` DESC
			";
			break;
			
		case "degree":
			$sort = "
				`Degree`.`code`,
				`Course`.`prefix`,
				`Course`.`number`,
				`FCAR`.`year` DESC,
				`User`.`lastName`,
				`User`.`firstName`
			";
			break;
			
		case "term":
		default:
			$sort = "
				`FCAR`.`year` DESC,
				`Course`.`prefix`,
				`Course`.`number`,
				`User`.`lastName`,
				`User`.`firstName`
			";
			break;
	}
	
	// 3. Send Query
	$query = "
		SELECT
			`FCAR`.`ID` AS ID,
			`FCAR`.`year`,
			`FCAR`.`term`,
			`FCAR`.`section`,
			`User`.`firstName`,
			`User`.`lastName`,
			`Degree`.`name` AS degreeName,
			`Degree`.`code`,
			`Course`.`name` AS courseName,
			`Course`.`prefix`,
			`Course`.`number`
		FROM `FCAR`
		JOIN `Degree`
		ON `Degree`.`ID` = `FCAR`.`fkDegree`
		JOIN `Course`
		ON `Course`.`ID` = `FCAR`.`fkCourse`
		JOIN `User`
		ON `User`.`ID` = `FCAR`.`fkUser`
		WHERE
			`User`.`fkDepartment` = :fkDepartment AND
			`FCAR`.`status` = 1 AND
			`FCAR`.`dateSubmitted` IS NOT NULL
		ORDER BY $sort
		LIMIT $limit
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	if (!$success || ($FCARs = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('index.php');
	}
	
	
	
	
	// Retrieve Current Sort
	$sort = parseStr($_GET['sort']);
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - List Submitted Assessment Reports</title>
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
			<section id="manageFCARs" class="bgField shadow corner center">
				<h2 class="title">List Submitted Assessment Reports</h2>
				
				<form name="exportFCARs" action="FCARsExport.php" method="POST">
					<table class="verticalTable padded widthFull">
						<tr>
							<th>Export</th>
							<th <?php if ($sort == 'user') echo 'class="selected"'; ?>><a href="<?php echo refreshPath(array('sort' => 'user')); ?>">User</a></th>
							<th <?php if ($sort == 'course') echo 'class="selected"'; ?>><a href="<?php echo refreshPath(array('sort' => 'course')); ?>">Course</a></th>
							<th <?php if ($sort == 'term' || $sort == '') echo 'class="selected"'; ?>><a href="<?php echo refreshPath(array('sort' => 'term')); ?>">Term</a></th>
							<th <?php if ($sort == 'degree') echo 'class="selected"'; ?>><a href="<?php echo refreshPath(array('sort' => 'degree')); ?>">Degree</a></th>
							<th>Reopen</th>
						</tr>
						
						<?php
							foreach ($FCARs as $FCAR)
							{
						?>
						<tr class="mouseHover">
							<td class="center"><input type="checkbox" name="FCARs[]" value="<?php echo $FCAR['ID']; ?>" /></td>
							<td class="clickable" onclick="window.location='FCARView.php?idFCAR=<?php echo $FCAR['ID']; ?>'"><?php echo $FCAR['lastName'] . ', ' . $FCAR['firstName']; ?></td>
							<td class="clickable" onclick="window.location='FCARView.php?idFCAR=<?php echo $FCAR['ID']; ?>'"><?php echo $FCAR['prefix'] . ' ' . $FCAR['number'] . ' ' . $FCAR['section'] . ' - ' . $FCAR['courseName']; ?></td>
							<td class="clickable" onclick="window.location='FCARView.php?idFCAR=<?php echo $FCAR['ID']; ?>'"><?php echo $FCAR['year'] . ' ' . $FCAR['term']; ?></td>
							<td class="clickable" onclick="window.location='FCARView.php?idFCAR=<?php echo $FCAR['ID']; ?>'"><?php echo $FCAR['code']; ?></td>
							<td class="center">
								<a href="FCARReopen.php?idFCAR=<?php echo $FCAR['ID']; ?>">
									<img src="media/reopen.png" title="Reopen Report for <?php echo $FCAR['prefix'] . ' ' . $FCAR['number'] . ' ' . $FCAR['section']; ?>" width="30" />
								</a>
							</td>
						</tr>
						<?php
							}
							unset($FCAR);
						?>
					</table>
					
					<div class="paging">
						<?php
							
							// Print Paging Information
							echo pagingHTML(PAGING_RADIUS, $page, $lastPage);
							
							// Print Total Number of Pages
							echo "<div>Showing " . ($page*RESULTS_PER_PAGE + 1) . " - " . min(($page + 1)*RESULTS_PER_PAGE, $numItems) . " of " . $numItems . "</div>";
						
						?>
					</div>
					
					<input type="submit" name="exportFCARsSubmit" value="Export Selected To Excel" />
				</form>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>