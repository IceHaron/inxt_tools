<?

/**
* 
* Карта вселенной
* 
**/

class map_index {
	
	public static function init() {
		return new self();
	}
	
	private function map_index() {

		// self::getRoute('Amarr','Jita');
		if (!isset($_GET['reg'])) {
			$jumps = db::query("SELECT DISTINCT `rfrom`.`name` as `fromName`, `rto`.`name` as `toName`
														FROM `gates` as `g`
															JOIN `systems` as `from` ON (`g`.`from` = `from`.`id`)
															JOIN `systems` as `to` ON (`g`.`to` = `to`.`id`)
															JOIN `regions` as `rfrom` ON (`rfrom`.`id` = `from`.`regionID`)
															JOIN `regions` as `rto` ON (`rto`.`id` = `to`.`regionID`)
														WHERE `rfrom`.`name` != `rto`.`name`
															AND `rfrom`.`id` NOT IN (10000004, 10000017, 10000019)
															AND `rto`.`id` NOT IN (10000004, 10000017, 10000019);");
			$dots = db::query("SELECT `id`, `name`, `pos_x`, `pos_y`, `pos_z` FROM `regions` WHERE `id` < 11000000 AND `id` NOT IN (10000004, 10000017, 10000019);");
			foreach ($jumps as $jump) {
				$routeDots[ $jump['fromName'] ][ $jump['toName'] ] = 1;
			}
			$map = array('dots' => $dots, 'jumps' => $jumps, 'routeDots' => $routeDots);
		} else {
			$map = universe::getRegionMap($_GET['reg']);
		}
/*    for ($i = 0; $i < count($jumps); $i++) {
			if (isset($jumps[$i])) {
				$jump = $jumps[$i];
				for ($j = 0; $j < count($jumps); $j++) {
					if (isset($jumps[$j])) {
						$subj = $jumps[$j];
						if ($subj['fromName'] == $jump['toName']) unset($jumps[$j]);
					}
				}
			}
		}*/
		$regionList = db::query("SELECT `id`, `name` FROM `regions` WHERE `id` < 11000000 ORDER BY `name`;");
		$regstr = '<select name="region" id="mapRegion"><option value="0">&mdash;&mdash;&mdash;Выберите регион&mdash;&mdash;&mdash;</option>';
		foreach ($regionList as $region) {
			if ($region['name'] == @$_GET['reg']) $sel = 'selected';
				else $sel = '';
			$regstr .= '<option ' . $sel . ' value="' . $region['id'] . '">' . $region['name'] . '</option>';
		}
		$regstr .= '</select>';
		$mainsupport = '
			<div id="control">
				<div class="startx"></div>
				<div class="x"></div>
				<div class="starty"></div>
				<div class="y"></div>
				' . $regstr . '
				<input type="button" value="Отрисовать" id="drawMap">
				<input type="button" value="К карте вселенной" id="resetMap">
			</div>
			<div id="strForMap">' . json_encode($map) . '</div>
			';
		$mainsupport .= '
			<form id="pathfinder" method="GET">
				<input type="text" id="fromSystem" name="from" placeholder="Отправная точка" autocomplete="off">
				<input type="text" id="toSystem" name="to" placeholder="Пункт назначения" autocomplete="off">
				' . (isset($_GET['reg']) ? '<input type="hidden" name="reg" value="' . $_GET['reg'] . '">' : '') . '
				<input type="submit" id="submitPath" disabled value="Проложить путь">
				<div id="systemSearchVariants" class="mapSSV">ololo</div>
			</form>';
		$maincontent = '';
		
		root::$_ALL['maincaption'] = 'EVE Universe Map';
		root::$_ALL['mainsupport'] = $mainsupport;
		root::$_ALL['maincontent'] = $maincontent;
		root::$_ALL['backtrace'][] = 'initialized map/index';
	}

	public static function getSystemsForRouter() {
		$from = db::escape($_GET['from']);
		$to = db::escape($_GET['to']);
		root::$_ALL['notemplate'] = TRUE;
		echo universe::getSystemsForRouter($from, $to);
	}

	public function getRoute($from, $to) {
		echo file_get_contents("http://api.eve-central.com/api/route/from/$from/to/$to");
		
	}

}