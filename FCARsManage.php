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
	
	
	// Require Login
	requireLogin();
	
	
	
	
	// Retrieve List of Open FCARs
	$query = "
		SELECT
			`FCAR`.`ID` AS ID,
			`FCAR`.`year`,
			`FCAR`.`term`,
			`FCAR`.`section`,
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
		WHERE
			`FCAR`.`fkUser` = :fkUser AND
			`FCAR`.`status` = 1 AND
			`FCAR`.`dateSubmitted` IS NULL
		ORDER BY `FCAR`.`dateModified` DESC
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkUser', $_SESSION['ID']);
	$success = $stmt->execute();
	if (!$success || ($openFCARs = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('index.php');
	}
	
	
	
	
	// Retrieve Total Number of Submitted (Closed) FCARs
	$query = "
		SELECT COUNT(*) AS num
		FROM `FCAR`
		WHERE
			`FCAR`.`fkUser` = :fkUser AND
			`FCAR`.`status` = 1 AND
			`FCAR`.`dateSubmitted` IS NOT NULL
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkUser', $_SESSION['ID']);
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
	$limit = "";
	$page = parseInt($_GET['page'], 0);
	$lastPage = max(ceil($numItems/RESULTS_PER_PAGE)-1, 0);
	$page = min($page, $lastPage);
	$limit = "LIMIT " . $page*RESULTS_PER_PAGE . ", " . RESULTS_PER_PAGE;
	
	// 2. Send Query
	$query = "
		SELECT
			`FCAR`.`ID` AS ID,
			`FCAR`.`year`,
			`FCAR`.`term`,
			`FCAR`.`section`,
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
		WHERE
			`FCAR`.`fkUser` = :fkUser AND
			`FCAR`.`status` = 1 AND
			`FCAR`.`dateSubmitted` IS NOT NULL
		ORDER BY `FCAR`.`dateModified` DESC
		$limit
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkUser', $_SESSION['ID']);
	$success = $stmt->execute();
	if (!$success || ($closedFCARs = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('index.php');
	}
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - Manage My Assessment Reports</title>
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
				<h2 class="title">Manage My Assessment Reports</h2>
				
				<h3>Open Assessment Reports</h3>
				<table class="verticalTable padded widthFull">
					<tr>
						<th>Course</th>
						<th>Term</th>
						<th>Degree</th>
						<th>Edit</th>
						<th>Submit</th>
						<th>Delete</th>
					</tr>
					
					<?php
						foreach ($openFCARs as $FCAR)
						{
					?>
					<tr class="mouseHover">
						<td class="clickable" onclick="window.location='FCARView.php?idFCAR=<?php echo $FCAR['ID']; ?>'"><?php echo $FCAR['prefix'] . ' ' . $FCAR['number'] . ' ' . $FCAR['section'] . ' - ' . $FCAR['courseName']; ?></td>
						<td class="clickable" onclick="window.location='FCARView.php?idFCAR=<?php echo $FCAR['ID']; ?>'"><?php echo $FCAR['year'] . ' ' . $FCAR['term']; ?></td>
						<td class="clickable" onclick="window.location='FCARView.php?idFCAR=<?php echo $FCAR['ID']; ?>'"><?php echo $FCAR['degreeName'] . ' (' . $FCAR['code'] . ')'; ?></td>
						<td class="center">
							<a href="FCAREdit.php?idFCAR=<?php echo $FCAR['ID']; ?>">
								<img src="media/edit.png" title="Edit Report for <?php echo $FCAR['prefix'] . ' ' . $FCAR['number'] . ' ' . $FCAR['section']; ?>" width="30" />
							</a>
						</td>
						<td class="center">
							<a href="FCARSubmit.php?idFCAR=<?php echo $FCAR['ID']; ?>">
								<img src="media/check.png" title="Submit Report for <?php echo $FCAR['prefix'] . ' ' . $FCAR['number'] . ' ' . $FCAR['section']; ?>" width="25" />
							</a>
						</td>
						<td class="center">
							<a href="FCARDisable.php?idFCAR=<?php echo $FCAR['ID']; ?>">
								<img src="media/cancel.png" title="Delete Report for <?php echo $FCAR['prefix'] . ' ' . $FCAR['number'] . ' ' . $FCAR['section']; ?>" width="30" />
							</a>
						</td>
					</tr>
					<?php
						}
						unset($FCAR);
					
						if (count($openFCARs) == 0)
						{
							echo '<tr><td class="center" colspan="6">You do not have any open assessment reports.</td></tr>';
						}
					?>
					
				</table>
				
				<a href="FCARNew.php" class="button">Create a New Assessment Report</a>
				
				<?php
					if (count($closedFCARs) > 0)
					{
				?>
				<h3>Submitted Assessment Reports</h3>
				<table class="verticalTable padded widthFull">
					<tr>
						<th>Course</th>
						<th>Term</th>
						<th>Degree</th>
					</tr>
					
					<?php
						foreach ($closedFCARs as $FCAR)
						{
					?>
					<tr class="mouseHover">
						<td class="clickable" onclick="window.location='FCARView.php?idFCAR=<?php echo $FCAR['ID']; ?>'"><?php echo $FCAR['prefix'] . ' ' . $FCAR['number'] . ' ' . $FCAR['section'] . ' - ' . $FCAR['courseName']; ?></td>
						<td class="clickable" onclick="window.location='FCARView.php?idFCAR=<?php echo $FCAR['ID']; ?>'"><?php echo $FCAR['year'] . ' ' . $FCAR['term']; ?></a></td>
						<td class="clickable" onclick="window.location='FCARView.php?idFCAR=<?php echo $FCAR['ID']; ?>'"><?php echo $FCAR['degreeName'] . ' (' . $FCAR['code'] . ')'; ?></a></td>
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
				<?php
					}
				?>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>