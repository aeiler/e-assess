<header>
	<div class="pageWidth">
		<div id="topLogo">
			<a href="index.php"><img class="logo" alt="CCU eAssess" src="media/logo.png" /></a>
		</div>
		
		<?php
			if (isset($_SESSION['firstName']) && isset($_SESSION['lastName']))
			{
		?>
		<div id="topWelcomeBox">
			<p>Hello, <?php echo $_SESSION['firstName'] . ' ' . $_SESSION['lastName']; ?></p>
			<a href="index.php">Home</a>
			<a href="userEdit.php">Edit Account</a>
			<a href="login.php?logout=true&msg=You+have+successfully+been+logged+out.">Logout</a>
		</div>
		<?php
			}
		?>
	</div>
</header>