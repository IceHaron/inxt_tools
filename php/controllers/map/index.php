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

		// root::$_ALL['checkpoints'][] = array('Map Init', microtime(1));
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
			<input type="text" class="systemSearch" data-searchmod="info" id="systemInfo" placeholder="Информация о системе" size="25">
			<div id="strForMap">' . json_encode($map) . '</div>
			';
		$maincontent = '
			<form id="pathfinder" method="GET">
				<input type="text" class="systemSearch" data-searchmod="path" id="fromSystem" name="from" placeholder="Отправная точка" autocomplete="off">
				<input type="text" class="systemSearch" data-searchmod="path" id="toSystem" name="to" placeholder="Пункт назначения" autocomplete="off">
				' . (isset($_GET['reg']) ? '<input type="hidden" name="reg" value="' . $_GET['reg'] . '">' : '') . '
				<input type="submit" id="submitPath" disabled value="Проложить путь">
				<div id="systemSearchVariants" class="mapSSV">ololo</div>
			</form>';
		
		root::$_ALL['maincaption'] = 'EVE Universe Map';
		root::$_ALL['mainsupport'] = $mainsupport;
		root::$_ALL['maincontent'] = $maincontent;
		root::$_ALL['backtrace'][] = 'initialized map/index';
	}

	public static function getSystemsForRouter() {
		$from = db::escape($_GET['from']);
		$to = db::escape($_GET['to']);
		root::$_ALL['notemplate'] = TRUE;
		root::$_ALL['checkpoints'][] = array('Map Init', microtime(1));
		echo universe::getSystemsForRouter($from, $to);
	}

	public function getRoute($from, $to) {
		echo file_get_contents("http://api.eve-central.com/api/route/from/$from/to/$to");
		
	}

}