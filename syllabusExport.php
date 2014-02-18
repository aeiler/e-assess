<?php
	
	// Include Utilities PHP File
	require_once('_utilities.php');
	
	
	// Initialize Error Array
	$errors = array();
	
	
	// Start Session
	session_start();
	
	
	// Connect To Database
	$pdo = dbConnect();
	
	
	// Require User Login
	requireLogin();
	
	
	
	
	// Require Provided Course ID
	$query = "
		SELECT
			`ID`,
			`name`,
			`description`,
			`prefix`,
			`number`
		FROM `Course`
		WHERE
			`ID` = :ID AND
			`fkDepartment` = :fkDepartment
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':ID', parseInt($_REQUEST['course']));
	$stmt->bindValue(':fkDepartment', $_SESSION['fkDepartment']);
	$success = $stmt->execute();
	if (!$success || ($courseInfo = $stmt->fetch(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('syllabusNew.php');
	}
	
	
	
	
	// Retrieve Objective List
	$query = "
		SELECT
			`ID`,
			`description`,
			`number`
		FROM `Objective`
		WHERE
			`fkCourse` = :fkCourse AND
			`status` = 1
		ORDER BY `number`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkCourse', $courseInfo['ID']);
	$success = $stmt->execute();
	if (!$success || ($objectives = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('syllabusNew.php');
	}
	
	
	
	
	// Retrieve SLO List
	$query = "
		SELECT DISTINCT
			`SLO`.`ID` AS ID,
			`SLO`.`code`,
			`SLO`.`description` AS description
		FROM `SLO`
		JOIN `SLOXObjective`
		ON `SLOXObjective`.`fkSLO` = `SLO`.`ID`
		JOIN `Objective`
		ON `Objective`.`ID` = `SLOXObjective`.`fkObjective`
		WHERE
			`Objective`.`fkCourse` = :fkCourse AND
			`SLO`.`status` = 1 AND
			`SLOXObjective`.`dateDisabled` IS NULL AND
			`Objective`.`status` = 1
		ORDER BY `SLO`.`code`
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkCourse', $courseInfo['ID']);
	$success = $stmt->execute();
	if (!$success || ($SLOs = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('syllabusNew.php');
	}
	
	
	
	
	// Retrieve SLOXObjective List
	$query = "
		SELECT
			`SLOXObjective`.`fkSLO`,
			`SLOXObjective`.`fkObjective`
		FROM `SLOXObjective`
		JOIN `Objective`
		ON `Objective`.`ID` = `SLOXObjective`.`fkObjective`
		WHERE
			`Objective`.`fkCourse` = :fkCourse AND
			`Objective`.`status` = 1 AND
			`SLOXObjective`.`dateDisabled` IS NULL
	";
	$stmt = $pdo->prepare($query);
	$stmt->bindValue(':fkCourse', $courseInfo['ID']);
	$success = $stmt->execute();
	if (!$success || ($SLOXObjective = $stmt->fetchAll(PDO::FETCH_ASSOC)) === FALSE)
	{
		redirect('syllabusNew.php');
	}
	
	
	
	
	// Construct File Name
	$filename = array();
	$filename[] = 'Syllabus';
	$filename[] = parseStr($_POST['year']) . parseStr($_POST['term']);
	$filename[] = $courseInfo['prefix'] . $courseInfo['number'];
	$filename[] = parseStr($_POST['section']);
	$filename[] = $_SESSION['lastName'];
	$filename = implode('_', $filename) . '.doc';
	
	
	
	
	// Set Word Document Headers
	header("Content-Type: application/vnd.ms-word");
	header("Content-Disposition: attachment;Filename=" . $filename);
?>


<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns:m="http://schemas.microsoft.com/office/2004/12/omml" xmlns="http://www.w3.org/TR/REC-html40">
	<head>
		<title>Class Syllabus</title>
		
		<meta http-equiv=Content-Type content="text/html; charset=us-ascii">
		<meta name=ProgId content=Word.Document>
		<meta name=Generator content="Microsoft Word 12">
		<meta name=Originator content="Microsoft Word 12">
		
		<!--[if gte mso 9]>
		<xml>
			<w:WordDocument>
				<w:View>Print</w:View>
				<w:Zoom>100</w:Zoom>
				<w:DoNotOptimizeForBrowser/>
			</w:WordDocument>
		</xml>
		<![endif]-->
		
		<style>
			<!-- /* Style Definitions */
			@page Section1
			{
				size: 8.5in 11.0in;
				margin: 1.0in 0.75in 1.0in 0.75in;
				mso-header-margin: .5in;
				mso-footer-margin: .5in;
				mso-paper-source: 0;
			}
			
			div.Section1
			{
				page: Section1;
			}
			-->
		</style>
		
		<style>
			body
			{
				font-size: 90%;
				font-family: sans-serif;
			}
			
			table
			{
				border-collapse: collapse;
				width: 7in;
			}
			th, td
			{
				border: 1px solid black;
				padding: 3px 5px;
			}
			th
			{
				font-weight: bold;
			}
			td.center
			{
				text-align: center;
			}
			
			h2
			{
				text-align: center;
			}
		</style>
	</head>
	
	<body lang='EN-US' style='tab-interval:.5in'>
		<div class="Section1">
			<h2>Class Syllabus</h2>
			
			<table>
				<tr>
					<th>Course</th>
					<td><?php echo $courseInfo['prefix'] . ' ' . $courseInfo['number'] . ' ' . parseStr($_POST['section']) . ' - ' . $courseInfo['name']; ?></td>
				</tr>
				<tr>
					<th>Academic Term</th>
					<td><?php echo parseStr($_POST['term']) . ' ' . parseStr($_POST['year']); ?></td>
				</tr>
				<tr>
					<th>Instructor</th>
					<td><?php echo $_SESSION['firstName'] . ' ' . $_SESSION['lastName']; ?></td>
				</tr>
			</table>
			
			<h3>Course Description</h3>
			<p><?php echo $courseInfo['description']; ?></p>
			
			<h3>Course Objectives</h3>
			<p>Upon successful completion of this course, students should be able to</p>
			<p>
				<?php
					foreach ($objectives as $objective)
						echo $objective['number'] . '. ' . $objective['description'] . '<br />' . "\n";
					unset($objective);
				?>
			</p>
			
			<h3>Student Learning Outcomes</h3>
			<p>Upon successful completion of this course, students should be able to</p>
			<p>
				<?php
					foreach ($SLOs as $SLO)
						echo $SLO['code'] . '. ' . $SLO['description'] . '<br />' . "\n";
					unset($SLO);
				?>
			</p>
			
			<h4>Course Objectives Mapped to Student Learning Outcomes</h4>
			<table>
				
				<tr>
					<th rowspan="2">Course Objectives</th>
					<th colspan="<?php echo count($SLOs); ?>">Student Learning Outcome</th>
				</tr>
				
				<tr>
					<?php
						foreach ($SLOs as $SLO)
							echo '<th>' . $SLO['code'] . '</th>';
						unset($SLO);
					?>
				</tr>
				
				<?php
					foreach ($objectives as $objective)
					{
						echo '<tr>';
						echo '<th>' . $objective['number'] . '</th>';
						foreach ($SLOs as $SLO)
						{
							echo '<td class="center">';
							if (in_array(array('fkSLO' => $SLO['ID'], 'fkObjective' => $objective['ID']), $SLOXObjective)) echo 'X';
							echo '</td>';
						}
						unset($SLO);
						echo '</tr>';
					}
					unset($objective);
				?>
			</table>
		</div>
	</body>
</html>