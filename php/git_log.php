<?
$rootfolder = isset($_SERVER['HOME']) ? $_SERVER['HOME'].'/inxt_tools' : $_SERVER['DOCUMENT_ROOT'];
$host = 'localhost';
$user = 'srv44030_tools';
$pw = '230105';
$base = 'srv44030_tools';

// Подключаемся к базе и ставим чарсет
$link = mysqli_connect($host, $user, $pw, $base);
mysqli_set_charset($link, "utf8");

$log = file($rootfolder.'/source/txt/git_log.txt');
$arr = array();
foreach ($log as $s) {
	$string = trim($s);
	if (preg_match('/^commit/', $string)) {
		if (isset($name)) $arr[$number]['name'] = $name;
		if (isset($desc)) $arr[$number]['desc'] = $desc;
		$number = preg_replace('/^commit\s+/', '', $string);
		$arr[$number] = array('author' => '', 'date' => '', 'name' => '', 'desc' => '');
		$nameTrigger = FALSE;
		$descTrigger = FALSE;
		$name = '';
		$desc = '';
	} else if (preg_match('/^Date\:/', $string)) {
		$date = date('Y-m-d H:i:s',strtotime(preg_replace('/^Date:\s+/', '', $string)));
		$arr[$number]['date'] = $date;
	} else if (preg_match('/^Author\:/', $string)) {
		$author = preg_replace('/^Author:\s+/', '', $string);
		$arr[$number]['author'] = $author;
	}
	if ($string == '') {
		if ($nameTrigger === FALSE) {
			$nameTrigger = TRUE;
			$descTrigger = FALSE;
		} else if ($descTrigger === FALSE) {
			$descTrigger = TRUE;
			$nameTrigger = FALSE;
		}
		else echo 'Something broken on commit ' . $number;
	} else {
		if ($nameTrigger) $name .= $string . '\n';
		else if ($descTrigger) $desc .= $string . '\n';
	}
}
if (isset($name)) $arr[$number]['name'] = $name;
if (isset($desc)) $arr[$number]['desc'] = $desc;
foreach ($arr as $commit => $info) {
	$query = "INSERT INTO `git_log` VALUES ('$commit','{$info['date']}','{$info['name']}','{$info['author']}','{$info['desc']}','{$info['name']}','{$info['desc']}','0') ON DUPLICATE KEY UPDATE `commit` = '$commit'";
	mysqli_query($link, $query);
	echo mysqli_error($link);
}