<?

/**
*	
*	Статистика систем
*	
**/

class systemstats_index {

	public static $regions;
	public static $stars;
	
	public static function init() {
		return new self();
	}
	
/**
*	
*	Конструктор: здесь мы собираем из файлов инфу о системах и регионах
*	
**/
	private function systemstats_index() {
		global $GAMINAS;
		
		foreach (db::query('SELECT * FROM `regions`') as $region) $regions[ $region['id'] ] = $region['name'];
		self::$regions = $regions;
		foreach (db::query('SELECT `sys`.*, `reg`.`name` `regname` FROM `systems` `sys` JOIN `regions` `reg` ON (`sys`.`regionID` = `reg`.`id`)') as $system) $systems[ $system['id'] ] = $system;
		self::$stars = $systems;
		$GAMINAS['backtrace'][] = 'Took region set and star set from DataBase';
	}
	
/**
*	
*	Метод, отбирающий системы для определенных регионов
*	@param	regions - строка, где записаны ID нужных регионов через запятую (если отсутствует, берется $_GET)
*	@return JSON-строка с информацией о системах
*	
**/
	public static function getSystems($regions = '') {
		global $GAMINAS;
		self::init();
		$GAMINAS['notemplate'] = TRUE;
		$r = $regions ? $regions : urldecode($_GET['regions']);
		$regionset = explode(',', $r);
		foreach ($regionset as $regID) {
			foreach (self::$stars as $starid => $starinfo) {
				if ($starinfo['regionID'] == $regID) {
					$res[ $starid ] = $starinfo;
					$res[ $starid ]['regionName'] = self::$regions[ $regID ];
				}
			}
		}
		echo json_encode($res);
	}
	
/**
*	
*	Метод, отображающий фильтры и график
*	@param	what - фильтр по регионам (не уверен, что пригодится, хотя посмотрим, лишним не будет)
*	@return void
*	
**/
	public static function show($what = '') {
		global $GAMINAS;
		self::init();
		
		// Определяем параметры
		$time = isset($_GET['time']) ? db::escape(urldecode($_GET['time'])) : 'hourly';
		$mode = isset($_GET['mode']) ? db::escape(urldecode($_GET['mode'])) : 'system';
		$subject = isset($_GET['subject']) ? self::parseStarList(urldecode($_GET['subject'])) : 'default';
		
		// Формируем строки для отображения на странице
		$maincaption = 'График активности в системах';
		$mainsupport = '';
		$maincontent = '<button name="draw" onclick="drawGraph();">Нарисовать</button><label>Ссылка на график: <input type="text" readonly name="link" id="graphLink" value="http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '"></label><div id="strForChart">' . self::getStringForGraph($time, $mode, $subject) . '</div>';
		
		// Формируем облако регионов
		foreach (self::$regions as $id => $name) $regions[$name] = $id;
		ksort($regions, SORT_STRING);
		$regionButtons = '';
		
		foreach ($regions as $regName => $regID) {
			$subclass = '';
			$newname = $regName;
			
			// Помечаем ВХ
			if (preg_match('/\w-\w\d{5}/', $regName) !== 0) {
				$subclass = ' wh';
				$newname = '&lt;WH&gt; ' . $regName;
			}
			
			$regionButtons .= '<div class="regButton' . $subclass . '" data-name="' . $regName . '" data-id="' . $regID . '">' . $newname . '</div>';
		}
		
		// Выбранные системы
		if ($subject != 'default') {
			$selectedStars = '';
			foreach ($subject['names'] as $i => $unit) {
				$escapedUnit = db::escape($unit);
				$q = "SELECT `id`, `regionID` FROM `systems` WHERE `name`='$escapedUnit';";
				$r = db::query($q);
				$ss = (float)$subject['secures'][$i];
				
				// Определяем цвет СС
				if ($ss == 1) $color = 'skyblue';
				if ($ss <= 0.9 && $ss > 0.6) $color = 'green';
				if ($ss <= 0.6 && $ss > 0.4) $color = 'yellow';
				if ($ss <= 0.4 && $ss > 0.0) $color = 'orange';
				if ($ss <= 0.0) $color = 'red';
				
				$selectedStars .= '<div data-name="' . $unit . '" data-id="' . $r[0]['id'] . '" data-regid="' . $r[0]['regionID'] . '" class="selectedStar"><div style="color:' . $color . '" class="ss">' . $subject['secures'][$i] . '</div>' . $unit . '<img src="/source/img/delete.png" class="deselectStar"></div>';
			}
		} else {
			$selectedStars = '<div data-name="Amarr" data-id="30002187" data-regid="10000043" class="selectedStar"><div style="color:skyblue" class="ss">1.0</div>Amarr<img src="/source/img/delete.png" class="deselectStar"></div><div data-name="Jita" data-id="30000142" data-regid="10000002" class="selectedStar"><div style="color:green" class="ss">0.9</div>Jita<img src="/source/img/delete.png" class="deselectStar"></div><div data-name="Dodixie" data-id="30002659" data-regid="10000032" class="selectedStar"><div style="color:green" class="ss">0.9</div>Dodixie<img src="/source/img/delete.png" class="deselectStar"></div><div data-name="Rens" data-id="30002510" data-regid="10000030" class="selectedStar"><div style="color:green" class="ss">0.9</div>Rens<img src="/source/img/delete.png" class="deselectStar"></div>';
		}
		
		
		// Запихиваем результаты в глобальную переменную
		$GAMINAS['maincaption'] = $maincaption;
		$GAMINAS['mainsupport'] = $mainsupport;
		$GAMINAS['maincontent'] = $maincontent;
		$GAMINAS['regionbuttons'] = $regionButtons;
		$GAMINAS['selectedstars'] = $selectedStars;
	}
	
/**
*	
*	Метод, выводящий на экран строку с параметрами для графика (только AJAX)
*	@return void
*	
**/
	public static function drawGraph() {
		global $GAMINAS;
		self::init();
		$GAMINAS['notemplate'] = TRUE;																							// Выключаем отображение макета
		
		// Определяем параметры
		$time = isset($_GET['time']) ? urldecode($_GET['time']) : 'hourly';
		$mode = isset($_GET['mode']) ? urldecode($_GET['mode']) : 'system';
		$subject = isset($_GET['subject']) ? self::parseStarList(urldecode($_GET['subject'])) : 'default';

		// Формируем строку в зависимости от параметров
		$res = self::getStringForGraph($time, $mode, $subject);
		
		echo $res;
	}
	
/**
*	
*	Метод разбора названий систем из форматной строки в годный массив
*	@param	string - форматная строка типа "Amarr_10,Jita_09,Dodixie_09,Rens_09"
*	@return array  - массив типа 
		array(2) {
			["names"]=>
			array(3) {
				[0]=>string(6) "5E6I-W"
				[1]=>string(6) "E-BYOS"
				[2]=>string(6) "5ED-4E"
			}
			["secures"]=>
			array(3) {
				[0]=>string(4) "-0.4"
				[1]=>string(4) "-0.3"
				[2]=>string(4) "-0.9"
			}
		}
*	
**/
	private static function parseStarList($string) {
		$escapedString = db::escape($string);
		$array['names'] = explode(',', preg_replace('/\s*\_\-*\d+/', '', $escapedString));
		$array['secures'] = explode(',', preg_replace('/(\D|\A)(\-?\d)/', '$1$2.', preg_replace('/[a-zA-Z0-9\s\-]+_/', '', $escapedString)));
		
		return $array;
	}
	
/**
*	
*	Метод, формирующий форматную строку для графика
*	@param	time - временной типа графика (ежечасный/ежедневный/ежемесячный)
*	@param	mode - тип графика система/регион
*	@param	subject - массив элементов, отформатированный в parseStarList()
*	@return string - форматная строка с информацией для графика
*	
**/	
	public static function getStringForGraph($time = 'hourly', $mode = 'system', $subject = 'default') {
		global $GAMINAS;
		self::init();
		
		// Определяем параметры
		if ($subject === 'default')
			$subject = array(
				'names' => array(
						'Amarr'
					, 'Jita'
					, 'Dodixie'
					, 'Rens'
				),
				'secures' => array(
						'1.'
					, '0.9'
					, '0.9'
					, '0.9'
				)
			);
			
		// Собираем строку элементов
		$query = implode("','", $subject['names']);
		
		if ($time == 'hourly') $timeHolder = 'ts';
		else $timeHolder = 'date';
		
		// Формируем запрос в БД
		if ($mode == 'system')
			$str = "SELECT unix_timestamp(`act`.`$timeHolder`) `ts`, `sys`.`name` `system`, `jumps` FROM `activity_$time` `act` JOIN `systems` `sys` ON (`act`.`system` = `sys`.`id`) WHERE `sys`.`name` IN ('$query');";
		else																																				// Запрос для регионального графика еще не готов, тут просто заглушка
			$str = "SELECT unix_timestamp(`act`.`ts`) `ts`, `sys`.`name` `system`, `jumps` FROM `activity_hourly` `act` JOIN `systems` `sys` ON (`act`.`system` = `sys`.`id`) WHERE `sys`.`name` IN ('Amarr', 'Jita', 'Rens');";
			
		// Отправляем запрос в БД
 		$q = db::query($str);
		
		// Разбираем запрос
		foreach ($q as $sysinfo) {
			$arr[ $sysinfo['ts'] ][ $sysinfo['system'] ] = $sysinfo['jumps'];
			if ($time == 'hourly')
				$resHead[ $sysinfo['system'] ] = $sysinfo['system'] . '(' . number_format($subject['secures'][ array_search($sysinfo['system'], $subject['names']) ], 1, '.', '') . ')';
			else																																			// Формат массива для регионального графика не готов, тут просто заглушка
				$resHead[ $sysinfo['system'] ] = $sysinfo['system'] . '(' . number_format($subject['secures'][ array_search($sysinfo['system'], $subject['names']) ], 1, '.', '') . ')';
		}
		
		// Формируем и возвращаем строку
		$res = '{"head":["' . implode('","', $resHead) . '"],"content":[';
		ksort($arr);
		
		foreach ($arr as $date => $systems) {
			$act = implode(',', $systems);
			$res .= '[new Date(' . $date . '000),' . $act . '],';
		}
		
		$res = substr($res, 0, -1) . ']}';
		
		return $res;
	}

/**
*	
*	Поиск систем по части названия (AJAX)
*	@param	search - часть названия системы
*	@return void
*	
**/	
	public static function searchSystems() {
		global $GAMINAS;
		self::init();
		$GAMINAS['notemplate'] = TRUE;

		$search = isset($_GET['search']) ? $_GET['search'] : 'nothing';
		$escapedString = db::escape($search);
		$q = "SELECT `systems`.*, `regions`.`name` `regionName` FROM `systems`	JOIN `regions` ON (`regions`.`id` = `systems`.`regionID`)	WHERE `systems`.`name` LIKE '%$escapedString%' ORDER BY `systems`.`name`";
		$r = db::query($q);
		if ($r != NULL) {
			$arr = array();
			foreach ($r as $system) {
				$ss = number_format($system['security'], 1, '.', '');
				$arr[] = array('id' => $system['id'], 'name' => $system['name'], 'security' => $ss, 'regionID' => $system['regionID'], 'regionName' => $system['regionName']);
			}
			echo json_encode($arr);
		} else echo 'NULL';
	}
}