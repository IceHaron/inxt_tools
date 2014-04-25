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
		$dots = db::query("SELECT id, name, pos_x, pos_y, pos_z FROM regions WHERE id < 11000000");
		$jumps = db::query("SELECT DISTINCT `rfrom`.`name` as `fromName`, `rto`.`name` as `toName` FROM `gates` as `g`
			JOIN `systems` as `from` ON (`g`.`from` = `from`.`id`)
			JOIN `systems` as `to` ON (`g`.`to` = `to`.`id`)
			JOIN `regions` as `rfrom` ON (`rfrom`.`id` = `from`.`regionID`)
			JOIN `regions` as `rto` ON (`rto`.`id` = `to`.`regionID`)
			WHERE `rfrom`.`name` != `rto`.`name`;");
		for ($i = 0; $i < count($jumps); $i++) {
			if (isset($jumps[$i])) {
				$jump = $jumps[$i];
				for ($j = 0; $j < count($jumps); $j++) {
					if (isset($jumps[$j])) {
						$subj = $jumps[$j];
						if ($subj['fromName'] == $jump['toName']) unset($jumps[$j]);
					}
				}
			}
		}
		$map = array('dots' => $dots, 'jumps' => $jumps);
		$regionList = db::query("SELECT `id`, `name` FROM srv44030_tools.regions WHERE `id` < 11000000 ORDER BY `name`;");
		$regstr = '<select name="region"><option value="0">&mdash;&mdash;&mdash;Выберите регион&mdash;&mdash;&mdash;</option>';
		foreach ($regionList as $region) {
			$regstr .= '<option value="' . $region['id'] . '">' . $region['name'] . '</option>';
		}
		$regstr .= '</select>';
		$maincontent = '<div id="control"><div class="startx"></div><div class="x"></div><div class="starty"></div><div class="y"></div>' . $regstr . '</div><div id="strForMap">' . json_encode($map) . '</div>';
		
		root::$_ALL['maincaption'] = 'EVE Universe Map';
		root::$_ALL['mainsupport'] = 'Содержание вспомогательного блока';
		root::$_ALL['maincontent'] = $maincontent;
		root::$_ALL['backtrace'][] = 'initialized map/index';
	}

	public function getRoute($from, $to) {
		echo file_get_contents("http://api.eve-central.com/api/route/from/$from/to/$to");
		
	}

}