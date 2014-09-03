<?

/**
*	
*	Тестовый класс, воротим здесь что захотим и всякие штуки тестируем именно тут.
*	Заодно это шаблон для создания других классов.
*	
**/

class navigator_index {
	
	public static function init() {
		return new self();
	}
	
	private function navigator_index() {
		
		root::$_ALL['maintitle'] = 'Навигатор';
		root::$_ALL['maincaption'] = 'Навигатор';
		root::$_ALL['mainsupport'] = '';
		root::$_ALL['backtrace'][] = 'initialized navigator/index';

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

		root::$_ALL['regionbuttons'] = $regionButtons;

		$q = "SELECT `groupID`, `groupName` FROM srv44030_tools.invgroups WHERE categoryID = 6;";
		$r = db::query($q);

		foreach ($r as $group) {
			$groups[ $group['groupID'] ] = $group['groupName'];
		}

		$q = "
			SELECT `invtypes`.`typeID`, `invtypes`.`typeName`, `invtypes`.`groupID`, `invtypes`.`raceID` FROM `invtypes`
			JOIN `invgroups` ON (`invgroups`.`groupID` = `invtypes`.`groupID`)
			WHERE `invgroups`.`categoryID` = 6 AND `invtypes`.`raceID` IS NOT NULL
			ORDER BY `invtypes`.`groupID`, `invtypes`.`raceID`, BINARY `invtypes`.`typeName`;";
		$r = db::query($q);
		$shipstr = '';

		foreach ($r as $ship) {
			$ships[ $groups[ $ship['groupID'] ] ][] = array('id' => $ship['typeID'], 'name' => $ship['typeName']);
			$shipstr .= ',' . $ship['typeID'];
		}

		$shipstr = substr($shipstr, 1);

		$str = '';

		foreach ($ships as $group => $shipSet) {
			$str .= '<optgroup label="' . $group . '">';

			foreach ($shipSet as $ship) {
				$str .= '<option data-group="' . $group . '" value="' . $ship['id'] . '">' . $ship['name'] . '</option>';
			}

			$str .= '</optgroup>';

		}

		$q = "
			SELECT `invtypes`.`typeID`,`invtypes`.`groupID`,`invtypes`.`typeName`,`dgmtypeattributes`.`attributeID`,
				COALESCE(`dgmtypeattributes`.`valueInt`,`dgmtypeattributes`.`valueFloat`) AS `value`
			FROM `invtypes`
			JOIN `dgmtypeattributes` ON (`dgmtypeattributes`.`typeID` = `invtypes`.`typeID`)
			WHERE `dgmtypeattributes`.`attributeID` IN (151,169,331,624,1282,1547,1932,1950)
				AND COALESCE(`dgmtypeattributes`.`valueInt`,`dgmtypeattributes`.`valueFloat`) != 0
				AND `invtypes`.`groupID` IN (78,300,747,762,763,782,1289)
			ORDER BY `dgmtypeattributes`.`attributeID` DESC, COALESCE(`dgmtypeattributes`.`valueInt`,`dgmtypeattributes`.`valueFloat`) DESC;";
		$r = db::query($q);
		var_dump($q, $r);

		foreach ($r as $item) {

			if ($item['typeID'] == 33512) continue;

			if (
			   $item['typeID'] == '28801' // Mid-grade Nomad Omega
			|| $item['typeID'] == '33529' // High-grade Ascendancy Omega
			|| $item['typeID'] == '33565' // Mid-grade Ascendancy Omega
			|| $item['typeID'] == '33952' // Low-grade Nomad Omega
			) $highImplants[ $item['typeID'] ] = $item;

			if ($item['attributeID'] == 331) $impSlot[ $item['typeID'] ] = $item['value'];

			else if ($item['attributeID'] == 1282 || $item['attributeID'] == 1932) $impStack[ $item['typeID'] ] = $item['value'];

			else if ($item['attributeID'] == 1547) $rigSize[ $item['typeID'] ] = $item['value'];

			else {

				switch ($item['groupID']) {
					case '78':
						$modules[ $item['groupID'] ][ $item['typeID'] ] = $item;
					break;
					
					case '300':
						$lowImplants[ $item['typeID'] ] = $item;
					break;
					
					case '747':
						$highImplants[ $item['typeID'] ] = $item;
					break;
					
					case '762':
						$modules[ $item['groupID'] ][ $item['typeID'] ] = $item;
					break;
					
					case '763':
						$modules[ $item['groupID'] ][ $item['typeID'] ] = $item;
					break;
					
					case '782':
						$rigs[ $item['typeID'] ] = $item;
					break;
					
					case '1289':
						$modules[ $item['groupID'] ][ $item['typeID'] ] = $item;
					break;
					
					default:
						$wtf[ $item['typeID'] ] = $item;
					break;
				}
			}
		}

		foreach ($lowImplants as $id => $impl) {
			$implants[ $impSlot[$id] ][$id] = $impl;
		}
		foreach ($highImplants as $id => $impl) {
			$implants[ $impSlot[$id] ][$id] = $impl;
		}

		ksort($implants);

		$implantString = '';

		foreach ($implants as $slot => $implantSet) {
			$implantString .= '<select id="implant_' . $slot . '"><option value="0" data-bonus="0">Implant slot ' . $slot . '</option>';

			foreach ($implantSet as $id => $implant) {
				if (isset($impStack[$id])) $stack = 'data-stack="' . $impStack[$id] . '"';
				else $stack = '';
				$implantString .= '<option value="' . $implant['typeID'] . '">' . $implant['typeName'] . '</option>';
			}

			$implantString .= '</select>';
		}

		foreach ($rigs as $id => $rig) {
			$rigArr[ $rigSize[$id] ][$id] = $rig;
		}

		$rigStr = json_encode($rigArr);
		$moduleStr = json_encode($modules);
		// var_dump($rigArr);


		root::$_ALL['maincontent'] = '
			<div id="warpTimeCalculator">
				<div id="ship">
					<select name="ship"><option value="0">--------</option>' . $str . '</select>
					<span id="shipAccel">1</span>
					<span id="shipDecel">1</span>
					<span id="lowSlots">0</span>
					<span id="rigSlots">0</span>
					<span id="rigSize">0</span>
					<span id="group">0</span>
					<span id="rigs">' . $rigStr . '</span>
					<span id="modules">' . $moduleStr . '</span>
					<table id="shipAttrs">
						<tr><th>Mass</th><td id="shipMass">0</td></tr>
						<tr><th>Agility</th><td id="shipAgil">0</td></tr>
						<tr><th>Warp Speed</th><td id="shipWS">0</td></tr>
					</table>
					<p>Модули</p>
					<div id="moduleSet"></div>
					<p>Риги</p>
					<div id="rigSet"></div>
				</div>
				<div id="imps"><p>Импланты</p>' . $implantString . '</div>
				<div id="skills">
					<p>Скиллы</p>
					<label>Spaceship Command skill level </label>
					<select id="ssc">
						<option value="1">1</option>
						<option value="2">2</option>
						<option value="3">3</option>
						<option value="4">4</option>
						<option value="5">5</option>
					</select><br/>
					<label>Advanced Spaceship Command skill level </label>
					<select id="assc">
						<option value="0">0</option>
						<option value="1">1</option>
						<option value="2">2</option>
						<option value="3">3</option>
						<option value="4">4</option>
						<option value="5">5</option>
					</select><br/>
					<label>Evasive Maneuvering skill level </label>
					<select id="em">
						<option value="0">0</option>
						<option value="1">1</option>
						<option value="2">2</option>
						<option value="3">3</option>
						<option value="4">4</option>
						<option value="5">5</option>
					</select>
				</div>
				<div id="timeBlock"></div>
			</div>';
	}

}