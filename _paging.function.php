<?php
	
	function pagingHTML($radius, $curPage, $lastPage)
	{
		// Prepare Return Variable
		$HTML = "<ul>\n";
		
		// Prepare Paging Link
		$link = $_SERVER['PHP_SELF'] . '?';
		foreach ($_GET as $key => $value)
			if (strcmp($key, 'page') != 0) $link .= $key . "=" . urlencode($value) . '&';
		$link .= 'page=';

		// Print Previous Pages
		if ($curPage != 0)
		{
			$HTML .= '<li class="pagingFirst"><a href="' . $link . 0 . "\">First</a></li>\n";
			$HTML .= '<li class="pagingPrevious"><a href="' . $link . ($curPage-1) . "\">Previous</a></li>\n";
		}
		if ($curPage < $radius)
			for ($i=0; $i<$curPage; $i++)
				$HTML .= '<li class="pagingNumber"><a href="' . $link . $i . '">' . ($i+1) . "</a></li>\n";
		else
			for ($i=max($curPage-$radius-max($radius-($lastPage-$curPage), 0), 0); $i<$curPage; $i++)
				$HTML .= '<li class="pagingNumber"><a href="' . $link . $i . '">' . ($i+1) . "</a></li>\n";
		
		// Print Current Page
		if ($lastPage != 0)
			$HTML .= '<li class="pagingCurrentNumber">' . ($curPage+1) . '</li>';
		
		// Print Next Pages
		if ($lastPage-$curPage < $radius)
			for ($i=$curPage+1; $i<=$lastPage; $i++)
				$HTML .= '<li class="pagingNumber"><a href="' . $link . $i . '">' . ($i+1) . "</a></li>\n";
		else
			for ($i=$curPage+1; $i<=min($curPage+$radius+max($radius-$curPage, 0), $lastPage); $i++)
				$HTML .= '<li class="pagingNumber"><a href="' . $link . $i . '">' . ($i+1) . "</a></li>\n";
		if ($curPage != $lastPage)
		{
			$HTML .= '<li class="pagingNext"><a href="' . $link . ($curPage+1) . "\">Next</a></li>\n";
			$HTML .= '<li class="pagingLast"><a href="' . $link . $lastPage . "\">Last</a></li>\n";
		}
		
		// Return HTML String
		$HTML .= "</ul>";
		return $HTML;
	}
	
?>