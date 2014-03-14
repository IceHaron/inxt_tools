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
		
		foreach (db::query('SELECT SQL_CACHE * FROM `regions`') as $region) $regions[ $region['id'] ] = $region['name'];
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
*	@return	строки с HTML-кодом чекбоксов регионов и систем
*	
**/
	
	public static function show($what = '') {
		global $GAMINAS;
		self::init();
		
		$time = isset($_GET['time']) ? urldecode($_GET['time']) : 'hourly';
		$mode = isset($_GET['mode']) ? urldecode($_GET['mode']) : 'system';
		$subject = isset($_GET['subject']) ? self::parseStarList(urldecode($_GET['subject'])) : 'default';
		
		$maincaption = 'График активности в системах';
		$mainsupport = '<label>Ссылка на график: <input type="text" name="link" id="graphLink" value="' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '"></label>';
		$maincontent = '<div id="strForChart">' . self::getStringForGraph($time, $mode, $subject) . '</div>';
		
		foreach (self::$regions as $id => $name) $regions[$name] = $id;
		ksort($regions, SORT_STRING);
		$regionButtons = '';
		
		foreach ($regions as $regName => $regID) {
			$subclass = '';
			$newname = $regName;
			if (preg_match('/\w-\w\d{5}/', $regName) !== 0) {
				$subclass = ' wh';
				$newname = '&lt;WH&gt; ' . $regName;
			}
			$regionButtons .= '<div class="regButton' . $subclass . '" data-name="' . $regName . '" data-id="' . $regID . '">' . $newname . '</div>';
		}
		
		$GAMINAS['maincaption'] = $maincaption;
		$GAMINAS['mainsupport'] = $mainsupport;
		$GAMINAS['maincontent'] = $maincontent;
		$GAMINAS['regionbuttons'] = $regionButtons;
	}
	
	public static function drawGraph() {
		global $GAMINAS;
		self::init();
		$GAMINAS['notemplate'] = TRUE;
		$time = isset($_GET['time']) ? urldecode($_GET['time']) : 'daily';
		$mode = isset($_GET['mode']) ? urldecode($_GET['mode']) : 'system';
		$regions = $_GET['region'] ? explode(',', urldecode($_GET['region'])) : 'default';
		$stars = $_GET['star'] ? self::parseStarList(urldecode($_GET['star'])) : 'default';

		$res = self::getStringForGraph($time, $mode, $regions, $stars);
		
		echo $res;
	}
	
	private static function parseStarList($string) {
		$array['names'] = explode(',', preg_replace('/\s*\_\-*\d+/', '', $string));
		$array['secures'] = explode(',', preg_replace('/(\D|\A)(\-?\d)/', '$1$2.', preg_replace('/[a-zA-Z0-9\s\-]+_/', '', $string)));
		
		return $array;
	}
	
	public static function getStringForGraph($time = 'hourly', $mode = 'system', $subject = 'default') {
		global $GAMINAS;
		self::init();
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
		$query = implode("','", $subject['names']);
		if ($mode == 'system')
			$str = "SELECT unix_timestamp(`act`.`ts`) `ts`, `sys`.`name` `system`, `jumps` FROM `activity_$time` `act` JOIN `systems` `sys` ON (`act`.`system` = `sys`.`id`) WHERE `sys`.`name` IN ('$query');";
		else
			$str = "SELECT unix_timestamp(`act`.`ts`) `ts`, `sys`.`name` `system`, `jumps` FROM `activity_hourly` `act` JOIN `systems` `sys` ON (`act`.`system` = `sys`.`id`) WHERE `sys`.`name` IN ('Amarr', 'Jita', 'Rens');";
 		$q = db::query($str);
		// var_dump($q);
		
		foreach ($q as $sysinfo) {
			$arr[ $sysinfo['ts'] ][ $sysinfo['system'] ] = $sysinfo['jumps'];
			if ($time == 'hourly')
				$resHead[ $sysinfo['system'] ] = $sysinfo['system'] . '(' . number_format($subject['secures'][ array_search($sysinfo['system'], $subject['names']) ], 1, '.', '') . ')';
			else
				$resHead[ $sysinfo['system'] ] = $sysinfo['system'] . '(' . number_format($subject['secures'][ array_search($sysinfo['system'], $subject['names']) ], 1, '.', '') . ')';
		}
		
		$res = '{"head":["' . implode('","', $resHead) . '"],"content":[';
		ksort($arr);
		
		foreach ($arr as $date => $systems) {
			$act = implode(',', $systems);
			$res .= '[new Date(' . $date . '000),' . $act . '],';
		}
		
		$res = substr($res, 0, -1) . ']}';
		
		return $res;
	}

}