<?

// http://eve-marketdata.com/developers/solarsystems.php ID систем
// http://wiki.eve-id.net/Main_Page Очень полезный сайтец с формулами и всякими штуками для АПИ

$rootfolder = isset($_SERVER['HOME']) ? $_SERVER['HOME'].'/gaminas' : $_SERVER['DOCUMENT_ROOT'];

$host = 'localhost';
$user = 'srv44030_gaminas';
$pw = '230105';
$base = 'srv44030_gaminas';

// Подключаемся к базе и ставим чарсет
$link = mysqli_connect($host, $user, $pw, $base);
mysqli_set_charset($link, "utf8");

// Селектим дневную стату
$q = mysqli_query($link, 'SELECT * FROM activity_daily');
if (!$q) echo mysqli_error($link);

// Собираем из этого удобоваримый массив
while ($row = mysqli_fetch_assoc($q)) {
	$dailytable[ $row['region'] ] = $row['activity'];
}

// Селектим месячную стату
$q = mysqli_query($link, 'SELECT * FROM activity_monthly');
if (!$q) echo mysqli_error($link);

// И из этого тоже собираем удобоваримый массив
while ($row = mysqli_fetch_assoc($q)) {
	$monthlytable[ $row['region'] ] = $row['activity'];
}

unset($q);																																				// Уничтожаем переменную, надеюсь, это исправит ошибку

// Тырим XML и превращаем его в JSON
$xml = new SimpleXMLElement('https://api.eveonline.com/map/Jumps.xml.aspx',0,TRUE);
$arr = json_decode(json_encode($xml), TRUE);
unset($xml);																																			// Уничтожаем переменную, надеюсь, это исправит ошибку
// Забираем инфу о системах и регионах. записанную в файлы
$stars = json_decode(file_get_contents($rootfolder . '/source/txt/systems.txt'), TRUE);
$regions = json_decode(file_get_contents($rootfolder . '/source/txt/regions.txt'), TRUE);

$cachetime = strtotime($arr['cachedUntil']);																		// Превращаем строку в таймстамп

foreach ($stars as $starID => $star) {
	$skeleton[ $regions[ $star['regionID'] ] ][ $starID ] = $star['name'];				// Из двух массивов собираем один
}

foreach ($skeleton as $region => $systems) {
	// Смотрим, какие данные у нас уже есть
	$dailywritten = json_decode($dailytable[ $region ], TRUE);
	$monthlywritten = json_decode($monthlytable[ $region ], TRUE);
	
	if ($dailywritten) {
		foreach($systems as $sysid => $system) {
			$dailyactivity = isset($dailywritten[ $system ]) ? $dailywritten[ $system ] : array();		// Активность системы забираем в отдельную переменную
			krsort($dailyactivity);
			$count = 48;

			foreach ($dailyactivity as $ts => $jumps) {
			
				if ($count > 0) {
					$daily[ $region ][ $system ][ $ts ] = $jumps;															// В итоговый массив заталкиваем информацию о прошлых часах
				}
				
				if ((int)date('d', strtotime('now')) - (int)date('d', $ts) == 1) {
					@$monthly[ $region ][ $system ][ strtotime(date('d M Y', $ts)) ] += $jumps;
					// var_dump(strtotime(date('d M Y', $ts)), ' - monthly <br/>');
				}
				
				$count--;
			}
			
			$daily[ $region ][ $system ][ $cachetime ] = '0';														// Ставим в 0 нынешний час, в XML отсутствуют системы с 0 активностью, так что эта строка необходима для полноты картины, в будущем наверное тоже надо убрать нулевые часы, но только если совсем беда с производительностью будет
		}
		
	} else {
	
		foreach($systems as $sysid => $system) {
			$daily[ $region ][ $system ][ $cachetime ] = '0';														// Ну это происходит если у нас вдруг отсутствуют изначальные данные, это уже неактуально, но  лишняя заглушка не помешает
		}
		
	}
	
	if ($monthlywritten) {
	
		foreach($systems as $sysid => $system) {
			$monthlyactivity = isset($monthlywritten[ $system ]) ? $monthlywritten[ $system ] : array();		// Активность системы забираем в отдельную переменную
			
			foreach ($monthlyactivity as $date => $jumps) {
				$monthly[ $region ][ $system ][ $date ] = $jumps;															// В итоговый массив заталкиваем информацию о прошлых часах
			}
			
		}
		
	}
	
}
unset($dailytable, $monthlytable);

// Вот и пришло время перебрать все, что нам пришло в XML
foreach($arr['result']['rowset']['row'] as $system) {
	$sysid = $system['@attributes']['solarSystemID'];
	$jumps = $system['@attributes']['shipJumps'];
	$daily[ $regions[ $stars[ $sysid ]['regionID'] ] ][ $stars[ $sysid ]['name'] ][ $cachetime ] = $jumps;
}

// $arr['currentTime'] - current time ETC
// $arr['result']['dataTime'] - time of query ETC

// Инициализируем управляющие переменные
// $trigger = array();
$query_arr = array();
$i = 0;

foreach ($regions as $id => $region) {
	$dailysystemset = $daily[ $region ];
	$dailywrite = json_encode($dailysystemset);
	$query_arr[] = "UPDATE `activity_daily` SET `activity` = '$dailywrite' WHERE `region` = '$region';";
	$monthlysystemset = $monthly[ $region ];
	$monthlywrite = json_encode($monthlysystemset);
	$query_arr[] = "UPDATE `activity_monthly` SET `activity` = '$monthlywrite' WHERE `region` = '$region';";
	// $query_arr[] = "INSERT INTO `activity_monthly` SET `activity` = '$monthlywrite', `region` = '$region';";
}

// echo '<pre>';
// var_dump($query_arr);
// echo '</pre>';

// echo 'Now: ' . $arr['currentTime'] . '(' . strtotime($arr['currentTime']) . ')<br/>
			// Cache time: ' . $arr['cachedUntil'] . '(' . strtotime($arr['cachedUntil']) . ')';

// Ну и наконец записываем информацию в базу
foreach ($query_arr as $query_str) {
	mysqli_query($link, trim($query_str, ','));
	echo mysqli_error($link);
}
?>