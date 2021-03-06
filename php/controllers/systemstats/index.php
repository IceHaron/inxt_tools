<?

/**
*	
*	Статистика систем
*	
**/

class systemstats_index {

	public static function init() {
		return new self();
	}
	
/**
*	
*	Конструктор
*	
**/
	private function systemstats_index() {
		
	}
	
/**
*	
*	Метод, отбирающий системы для определенных регионов
*	@param	regions - строка, где записаны ID нужных регионов через запятую (если отсутствует, берется $_GET)
*	@return JSON-строка с информацией о системах
*	
**/
	public static function getSystems($regions = '') {
		root::$_ALL['notemplate'] = TRUE;
		$r = $regions ? $regions : urldecode($_GET['regions']);
		echo universe::getSystems($r);
	}
	
/**
*	
*	Метод, отображающий фильтры и график
*	@param	what - фильтр по регионам (не уверен, что пригодится, хотя посмотрим, лишним не будет)
*	@return void
*	
**/
	public static function show($what = '') {
		// Определяем параметры
		$time = isset($_GET['time']) ? db::escape(urldecode($_GET['time'])) : 'hourly';
		$mode = isset($_GET['mode']) ? db::escape(urldecode($_GET['mode'])) : 'system';
		$subject = isset($_GET['subject']) ? self::parseSystemList(urldecode($_GET['subject'])) : 'default';
		
		// Формируем строки для отображения на странице
		$maincaption = 'График активности в системах (жмякните чтобы скрыть/показать фильтры)';
		$mainsupport = '';
		$maincontent = '<button name="draw" onclick="drawGraph();">Нарисовать</button><label>Ссылка на график: <input type="text" readonly name="link" id="graphLink" value="http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '"></label><div id="strForChart">' . self::getStringForGraph($time, $mode, $subject) . '</div>';
		
		// Формируем облако регионов
		foreach (universe::$regions as $id => $name) $regions[$name] = $id;
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
			$selectedSystems = '';
			foreach ($subject['names'] as $i => $unit) {
				$escapedUnit = db::escape($unit);
				$q = "SELECT `s`.`id`, `s`.`regionID`, `r`.`name` AS `regionName` FROM `systems` AS `s` JOIN `regions` AS `r` ON (`r`.`id` = `s`.`regionID`) WHERE `s`.`name`='$escapedUnit';";
				$r = db::query($q);
				$ss = (float)$subject['secures'][$i];
				
				// Определяем цвет СС
				if ($ss == 1) $color = 'skyblue';
				if ($ss <= 0.9 && $ss > 0.6) $color = 'green';
				if ($ss <= 0.6 && $ss > 0.4) $color = 'yellow';
				if ($ss <= 0.4 && $ss > 0.0) $color = 'orange';
				if ($ss <= 0.0) $color = 'red';
				
				$selectedSystems .= '<div data-name="' . $unit . '" data-id="' . $r[0]['id'] . '" data-regid="' . $r[0]['regionID'] . '" class="selectedSystem"><div style="color:' . $color . '" class="ss">' . $subject['secures'][$i] . '</div>' . $unit . '<img src="/source/img/delete.png" class="deselectSystem"><div class="sysRegHolder"><div class="sysRegion">' . $r[0]['regionName'] . '</div></div></div>';
			}
		} else {
			$selectedSystems = '<div class="selectedSystem" data-regid="10000043" data-id="30002187" data-name="Amarr"><div class="ss" style="color:skyblue">1.0</div>Amarr<img class="deselectSystem" src="/source/img/delete.png"><div class="sysRegHolder"><div class="sysRegion">Domain</div></div></div><div class="selectedSystem" data-regid="10000030" data-id="30002510" data-name="Rens"><div class="ss" style="color:green">0.9</div>Rens<img class="deselectSystem" src="/source/img/delete.png"><div class="sysRegHolder"><div class="sysRegion">Heimatar</div></div></div><div class="selectedSystem" data-regid="10000002" data-id="30000142" data-name="Jita"><div class="ss" style="color:green">0.9</div>Jita<img class="deselectSystem" src="/source/img/delete.png"><div class="sysRegHolder"><div class="sysRegion">The Forge</div></div></div><div class="selectedSystem" data-regid="10000032" data-id="30002659" data-name="Dodixie"><div class="ss" style="color:green">0.9</div>Dodixie<img class="deselectSystem" src="/source/img/delete.png"><div class="sysRegHolder"><div class="sysRegion">Sinq Laison</div></div></div>';
		}
		
		
		// Запихиваем результаты в глобальную переменную
		root::$_ALL['maincaption'] = $maincaption;
		root::$_ALL['mainsupport'] = $mainsupport;
		root::$_ALL['maincontent'] = $maincontent;
		root::$_ALL['regionbuttons'] = $regionButtons;
		root::$_ALL['selectedsystems'] = $selectedSystems;
	}
	
/**
*	
*	Метод, выводящий на экран строку с параметрами для графика (только AJAX)
*	@return void
*	
**/
	public static function drawGraph() {
		root::$_ALL['notemplate'] = TRUE;																							// Выключаем отображение макета
		
		// Определяем параметры
		$time = isset($_GET['time']) ? urldecode($_GET['time']) : 'hourly';
		$mode = isset($_GET['mode']) ? urldecode($_GET['mode']) : 'system';
		$subject = $_GET['subject'] ? self::parseSystemList(urldecode($_GET['subject'])) : 'default';

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
	private static function parseSystemList($string) {
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
*	@param	subject - массив элементов, отформатированный в parseSystemList()
*	@return string - форматная строка с информацией для графика
*	
**/	
	public static function getStringForGraph($time = 'hourly', $mode = 'system', $subject = 'default') {
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
			$str = "SELECT unix_timestamp(`act`.`ts`) `ts`, `sys`.`name` `system`, `jumps` FROM `activity_hourly` `act` JOIN `systems` `sys` ON (`act`.`system` = `sys`.`id`) WHERE `sys`.`name` IN ('Amarr', 'Jita', 'Dodixie, 'Rens');";
			
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
		root::$_ALL['notemplate'] = TRUE;
		echo universe::searchSystems();
	}
}