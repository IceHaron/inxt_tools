<?
$rootfolder = isset($_SERVER['HOME']) ? $_SERVER['HOME'].'/inxt_tools' : $_SERVER['DOCUMENT_ROOT'];

$host = 'localhost';
$user = 'srv44030_tools';
$pw = '230105';
$base = 'srv44030_tools';

// Подключаемся к базе и ставим чарсет
$link = mysqli_connect($host, $user, $pw, $base);
mysqli_set_charset($link, "utf8");

// Тырим XML и превращаем его в JSON
$xml = new SimpleXMLElement('https://api.eveonline.com/map/Jumps.xml.aspx',0,TRUE);
$arr = json_decode(json_encode($xml), TRUE);
unset($xml);																																		// Уничтожаем переменную

$cachetime = $arr['cachedUntil'];

$q = mysqli_query($link, 'SELECT `id` FROM `systems`');
while ($s = mysqli_fetch_assoc($q)) {
	$systems[ $s['id'] ] = 0;
}

foreach ($arr['result']['rowset']['row'] as $sysinfo) {
	$systems[ $sysinfo['@attributes']['solarSystemID'] ] = (int) $sysinfo['@attributes']['shipJumps'];
}

$str = '';

foreach ($systems as $sysid => $sysjumps) {
	$str .= "('$cachetime', '$sysid', '$sysjumps'),";
}

$insert = 'INSERT INTO `activity_hourly` (`ts`, `system`, `jumps`) VALUES ' . substr($str, 0, -1) . ' ON DUPLICATE KEY UPDATE `jumps` = `jumps`';
$e = mysqli_query($link, $insert);
if (!$e) echo mysqli_error($link);

$replace = "REPLACE INTO `activity_daily` (`date`,`system`,`jumps`)
	SELECT date_format(`ts`, '%Y-%m-%d') `date`, `system`, sum(`jumps`) 
		FROM `srv44030_tools`.`activity_hourly` 
		WHERE date_format('$cachetime', '%j') - date_format(`ts`, '%j') = 1 
			OR date_format('$cachetime', '%j') - date_format(`ts`, '%j') < 0
		GROUP BY `date`, `system`";
$r = mysqli_query($link, $replace);
if (!$r) echo mysqli_error($link);

$delete = "DELETE FROM `srv44030_tools`.`activity_hourly` WHERE unix_timestamp('$cachetime') - unix_timestamp(`ts`) > 172800;";
$e = mysqli_query($link, $delete);
if (!$e) echo mysqli_error($link);

// echo('<pre>');
// var_dump($q);
// echo('</pre>');
echo 'Memory peak usage in bytes: ' . memory_get_peak_usage();
