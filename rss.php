<?php
require('rss.class.php');
$db = mysqli_connect(); //Create DB instance
$rss = new RSS($db); //Create RSS instance
$json = $rss->getJSON(); //Get the newest JSON from github
$points = $rss->parsePoints($json); //Parse the JSON correctly
$rss->parseDifference($points); //See if there is any difference
$feed = $rss->getDB(); //Get all previous changes

//Start actually parsing the RSS

header("Content-Type: application/xml; charset=UTF-8");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
?>
<rss version="2.0">
<channel>
<title>Recent TrumpTracker updates</title>
<link>https://trumptracker.github.io</link>
<description>RSS feed for all policy updates on Trump's presidency</description>
<language>en-US</language>
<?
while($row = mysqli_fetch_assoc($feed)) {
	echo "<item>\n";
	echo "<guid isPermaLink='false'>$row[ID]</guid>\n";
	echo "<title>$row[title]</title>\n";
	echo "<link>$row[url]</link>\n";
	echo "<description>$row[content]</description>\n";
	$time = date('D, d M Y H:i:s T',$row['time']);
	echo "<pubDate>$time</pubDate>\n";
	echo "</item>\n";
}
?>
</channel>
</rss>