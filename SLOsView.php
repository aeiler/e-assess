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
	
	
	
	
	// Retrieve Current List of SLOs
	$sort = (!isblank($_GET['sort'])) ? '`Degree`.`ID` = ' . intval($_GET['sort']) . ' AND' : '';
	$query = "
		SELECT DISTINCT
			`SLO`.`ID` AS ID,
			`SLO`.`code` AS code,
			`SLO`.`description`,
			GROUP_CONCAT(`Degree`.`code` ORDER BY `Degree`.`code` SEPARATOR ', ') AS degrees
		FROM `SLO`
		JOIN `SLOXDegree`
		ON `SLOXDegree`.`fkSLO` = `SLO`.`ID`
		JOIN `Degree`
		ON `SLOXDegree`.`fkDegree` = `Degree`.`ID`
		WHERE
			$sort
			`SLO`.`status` = 1 AND
			`Degree`.`fkDepartment` = :fkDepartment
		GROUP BY `SLO`.`ID`
		ORDER BY `SLO`.`code`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	if (!$success || ($SLOs = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('index.php');
	}
	
	
	
	
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
	if (!$success || ($degrees = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('index.php');
	}
	
	
	
	
	// Prepare HTML Page Data
	$PAGEDATA = array();
	$PAGEDATA['sort'] = (isset($_GET['sort'])) ? $_GET['sort'] : '';
	
?>




<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Title and Icon -->
		<title>eAssess CCU - View SLOs</title>
		<link rel="icon" href="media/favicon.ico" />
		
		<!-- Meta Information -->
		<meta charset="utf-8" />
		
		<!-- Stylesheets -->
		<link rel="stylesheet" type="text/css" href="css/_reset.css" />
		<link rel="stylesheet" type="text/css" href="css/_globalStyles.css" />
		
		<!-- JQuery -->
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
		
		<!-- Custom Javascript -->
		<script type="text/javascript">
			$(function() {
				$('select[name="sort"]', 'form[name="sort"]').change(function() {
					$('form[name="sort"]').submit();
				});
			});
		</script>
	</head>

	<body>
		<?php require('html/header.php'); ?>

        <div id="siteContainer" class="pageWidth">
			<section class="bgField shadow corner center">
				<h2 class="title">View SLOs</h2>
				
				<form name="sort" action="" method="GET">
					<label>
						Show Only From Degree:
						<select name="sort">
							<option value="">Show All</option>
							<?php
								foreach ($degrees as $degree)
								{
									echo '<option value="' . $degree['ID'] . '"';
									if ($PAGEDATA['sort'] == $degree['ID']) echo ' selected';
									echo '>' . $degree['name'] . ' (' . $degree['code'] . ')</option>' . "\n";
								}
								unset($degree);
							?>
						</select>
					</label>
				</form>
				
				<table class="verticalTable padded widthFull">
					<tr>
						<th>Code</th>
						<th>Description</th>
						<th>Degrees</th>
					</tr>
					
					<?php
						foreach ($SLOs as $SLO)
						{
					?>
					<tr>
						<td><?php echo $SLO['code']; ?></td>
						<td><?php echo $SLO['description']; ?></td>
						<td><?php echo $SLO['degrees']; ?></td>
					</tr>
					<?php
						}
						unset($SLO);
					?>
					
				</table>
				
				<a href="SLOsExport.php" class="button">Export to Excel</a>
			</section>
			
			<?php require('html/footer.php'); ?>
		</div>
	</body>
</html>