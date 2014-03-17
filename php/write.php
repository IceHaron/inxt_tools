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

$q = 'INSERT INTO `activity_hourly` (`ts`, `system`, `jumps`) VALUES ' . substr($str, 0, -1) . ' ON DUPLICATE KEY UPDATE `jumps` = `jumps`';
$e = mysqli_query($link, $q);
if (!$e) echo mysqli_error($link);

$str = '';

$q = "SELECT date_format(`ts`, '%Y-%m-%d') date, `system`, sum(`jumps`) day_jumps FROM `activity_hourly` WHERE date_format('$cachetime', '%j') - date_format(ts, '%j') != 0 AND date_format('$cachetime', '%j') - date_format(ts, '%j') !=2 GROUP BY `system`";
$r = mysqli_query($link, $q);
echo mysqli_error($link);
if ($r) while ($arr = mysqli_fetch_assoc($r)) {
	$str .= "('{$arr['date']}', '{$arr['system']}', '{$arr['day_jumps']}'),";
}

$q = "INSERT INTO `activity_daily` (`date`, `system`, `jumps`) VALUES " . substr($str, 0, -1) . ' ON DUPLICATE KEY UPDATE `jumps` = `jumps`';
$e = mysqli_query($link, $q);
// if (!$e) echo mysqli_error($link);

// echo('<pre>');
// var_dump($q);
// echo('</pre>');
// echo 'Memory peak usage in bytes: ' . memory_get_peak_usage();
