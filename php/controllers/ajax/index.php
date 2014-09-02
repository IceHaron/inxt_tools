<?

/**
*	
*	Тестовый класс, воротим здесь что захотим и всякие штуки тестируем именно тут.
*	Заодно это шаблон для создания других классов.
*	
**/

class ajax_index {
	
	public static function init() {
		return new self();
	}
	
	private function ajax_index() {
	}

	public static function steam($steamID) {
		$uid = $_GET['uid'] ? db::escape($_GET['uid']) : $steamID;
		$str = '';
		// Profile
		$profile_str = file_get_contents('http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=0BE85074D210A01F70B48205C44D1D56&steamids=' . $uid);
		// Вот эта строка - и есть заглушка на случай если стим недоступен
		// $profile_str = '{"response":{"players":[{"personaname":"Dummy","profileurl":"gaminas.ice","avatar":"http://placehold.it/32x32"}]}}';
		$str .= '{"profile":' . $profile_str . '}';

		echo $str;
	}

	public static function calcTime() {
		$etuor = json_decode($_POST['route'], TRUE);
		$mass = db::escape($_POST['mass']);
		$agil = db::escape($_POST['agil']);
		$warp = db::escape($_POST['warp']);
		$acc = db::escape($_POST['acc']);
		$dec = db::escape($_POST['dec']);
		array_multisort($etuor);
		foreach ($etuor as $name => $range) $route[$range] = $name;
		echo universe::calcRouteTime($route, $mass, $agil, $warp, $acc, $dec);
	}

	public static function getShipAttrs() {
		$ship = db::escape($_GET['ship']);
		$q = "
			SELECT 
				`invtypes`.`typeName`,
				`invtypes`.`groupID`,
				`invtypes`.`mass`,
				`dgmtypeattributes`.`attributeID`,
				COALESCE(`dgmtypeattributes`.`valueInt`,
				`dgmtypeattributes`.`valueFloat`) AS `value`
			FROM `invtypes`
			JOIN `dgmtypeattributes` ON (`dgmtypeattributes`.`typeID` = `invtypes`.`typeID`)
			WHERE `invtypes`.`typeID` = $ship AND `dgmtypeattributes`.`attributeID` IN (12,70,600,1137,1547);";
		$r = db::query($q);

		foreach ($r as $attr) $output[ $attr['attributeID'] ] = $attr['value'];

		$output['mass'] = $r[0]['mass'];
		$output['group'] = $r[0]['groupID'];

		echo json_encode($output);

	}

	public static function calcAttrs() {
		$warpAdd = 0;
		$warpMult = 1;
		$agilMult = 1;
		$ascendancy = 1;
		$nomad = 1;
		$i = 0;
		$ids = array();
		$agilbonuses = array();
		$count = array();
		foreach ($_POST['set'] as $item) {
			if ($item != 0) $ids[] = $item;
			if (!isset($count[$item])) $count[$item] = 1;
			else $count[$item]++;
		}

		if (count($ids) == 0) exit('{"warpadd":0,"warpmult":1,"agilmult":1}');

		$str = implode(',', $ids);
		
		$q = "
			SELECT `invtypes`.`typeID`,`invtypes`.`groupID`,`invtypes`.`typeName`,`dgmtypeattributes`.`attributeID`,
				COALESCE(`dgmtypeattributes`.`valueInt`,`dgmtypeattributes`.`valueFloat`) AS `value`
			FROM `invtypes`
			JOIN `dgmtypeattributes` ON (`dgmtypeattributes`.`typeID` = `invtypes`.`typeID`)
			WHERE `dgmtypeattributes`.`attributeID` IN (151,169,624,1282,1932,1950)
				AND COALESCE(`dgmtypeattributes`.`valueInt`,`dgmtypeattributes`.`valueFloat`) != 0
				AND `invtypes`.`typeID` IN ($str)
			ORDER BY `dgmtypeattributes`.`attributeID` DESC, COALESCE(`dgmtypeattributes`.`valueInt`,`dgmtypeattributes`.`valueFloat`) DESC;";
		$r = db::query($q);
		foreach ($r as $item) {
			$arr[ $item['attributeID'] ][ $item['typeID'] ] = (float)$item['value'];
			$types[ $item['typeID'] ] = $item['groupID'];
		}

		if (isset($arr[1950]))
			foreach ($arr[1950] as $id => $wsadd) $warpAdd += $wsadd * $count[$id];

		if (isset($arr[1932]))
			foreach ($arr[1932] as $stack) $ascendancy *= $stack;

		if (isset($arr[1282]))
			foreach ($arr[1282] as $stack) $nomad *= $stack;

		if (isset($arr[624]))
			foreach ($arr[624] as $id => $mult) {
				if ($types[$id] == 300) $mult *= $ascendancy;
				for ($c = 1; $c <= $count[$id]; $c++) {
					$wsmult = $mult;
					if ($types[$id] == 782) {
						$i++;
						$wsmult *= pow(0.5, pow(($i-1)/2.22292081, 2));
					}
					$warpMult *= 1 + 0.01 * $wsmult;
				}
			}

		$i = 0;
		if (isset($arr[169])) {
			$agilbonuses += $arr[169];
		}
		if (isset($arr[151])) {
			$agilbonuses += $arr[151];
		}
		if ($agilbonuses) {
			asort($agilbonuses);
			foreach ($agilbonuses as $id => $mult) {
				if ($types[$id] == 300) $mult *= $nomad;
				for ($c = 1; $c <= $count[$id]; $c++) {
					$agmult = $mult;
					if ($types[$id] == 762 || $types[$id] == 763 || $types[$id] == 782) {
						$i++;
						$agmult *= pow(0.5, pow(($i-1)/2.22292081, 2));
					}
					$agilMult *= 1 + 0.01 * $agmult;
				}
			}
		}
		echo json_encode(array('warpadd' => $warpAdd, 'warpmult' => $warpMult, 'agilmult' => $agilMult));
	}

}