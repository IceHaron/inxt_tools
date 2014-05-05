<?

/**
*	
*	Тестовый класс, воротим здесь что захотим и всякие штуки тестируем именно тут.
*	Заодно это шаблон для создания других классов.
*	
**/

class map_index {
	
	public static function init() {
		return new self();
	}
	
	private function map_index() {

		// self::getRoute('Amarr','Jita');
		if (!isset($_GET['reg'])) {
			$jumps = db::query("SELECT DISTINCT `rfrom`.`name` as `fromName`, `rto`.`name` as `toName` FROM `gates` as `g`
				JOIN `systems` as `from` ON (`g`.`from` = `from`.`id`)
				JOIN `systems` as `to` ON (`g`.`to` = `to`.`id`)
				JOIN `regions` as `rfrom` ON (`rfrom`.`id` = `from`.`regionID`)
				JOIN `regions` as `rto` ON (`rto`.`id` = `to`.`regionID`)
				WHERE `rfrom`.`name` != `rto`.`name`;");
			$dots = db::query("SELECT `id`, `name`, `pos_x`, `pos_y`, `pos_z` FROM `regions` WHERE `id` < 11000000");
		} else {
			$jumps = db::query("SELECT DISTINCT `from`.`name` as `fromName`, `to`.`name` as `toName`, `rto`.`name` as `toReg` FROM `gates` as `g`
				JOIN `systems` as `from` ON (`g`.`from` = `from`.`id`)
				JOIN `systems` as `to` ON (`g`.`to` = `to`.`id`)
				JOIN `regions` as `rfrom` ON (`rfrom`.`id` = `from`.`regionID`)
				JOIN `regions` as `rto` ON (`rto`.`id` = `to`.`regionID`)
				WHERE `rfrom`.`name` = '{$_GET['reg']}';");
			$foreign = '';
			foreach ($jumps as $jump) {
				if ($jump['toReg'] != $_GET['reg']) $foreign .= ", '" . $jump['toName'] . "'";
			}
			$dots = db::query("SELECT `s`.`id`, `s`.`name`, `s`.`pos_x`, `s`.`pos_y`, `s`.`pos_z`, `r`.`name` as `regName` FROM `systems` as `s` JOIN `regions` as `r` ON (`s`.`regionID` = `r`.`id`) WHERE `r`.`name` = '{$_GET['reg']}' OR `s`.`name` IN (" . substr($foreign, 2) . ")");
		}
/*		for ($i = 0; $i < count($jumps); $i++) {
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
		$map = array('dots' => $dots, 'jumps' => $jumps);
		$regionList = db::query("SELECT `id`, `name` FROM `regions` WHERE `id` < 11000000 ORDER BY `name`;");
		$regstr = '<select name="region" id="mapRegion"><option value="0">&mdash;&mdash;&mdash;Выберите регион&mdash;&mdash;&mdash;</option>';
		foreach ($regionList as $region) {
			if ($region['name'] == @$_GET['reg']) $sel = 'selected';
				else $sel = '';
			$regstr .= '<option ' . $sel . ' value="' . $region['id'] . '">' . $region['name'] . '</option>';
		}
		$regstr .= '</select>';
		$maincontent = '<div id="control"><div class="startx"></div><div class="x"></div><div class="starty"></div><div class="y"></div>' . $regstr . '<input type="button" value="Отрисовать" id="drawMap"></div><div id="strForMap">' . json_encode($map) . '</div>';
		
		root::$_ALL['maincaption'] = 'EVE Universe Map';
		root::$_ALL['mainsupport'] = 'Содержимое вспомогательного блока';
		root::$_ALL['maincontent'] = $maincontent;
		root::$_ALL['backtrace'][] = 'initialized map/index';
	}

	public function getRoute($from, $to) {
		echo file_get_contents("http://api.eve-central.com/api/route/from/$from/to/$to");
		
	}

}