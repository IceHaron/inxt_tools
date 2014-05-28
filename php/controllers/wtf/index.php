<?

/**
*	
*	Тестовый класс, воротим здесь что захотим и всякие штуки тестируем именно тут.
*	Заодно это шаблон для создания других классов.
*	
**/

class wtf_index {
	
	public static function init() {
		return new self();
	}
	
	private function wtf_index() {
		
		root::$_ALL['maintitle'] = 'Тестовая страница';
		root::$_ALL['maincaption'] = 'Заголовок страницы';
		root::$_ALL['mainsupport'] = 'Содержимое вспомогательного блока';
		root::$_ALL['maincontent'] = 'Содержимое центрального блока';
		root::$_ALL['backtrace'][] = 'initialized wtf/index';
	}

	public static function version() {

		$maincontent = '<div class="patchlog">';
		$patches = db::query("SELECT `date`, `manual_name`, `manual_desc` FROM `git_log` WHERE `approved` = 1 ORDER BY `date` DESC");
		foreach ($patches as $patch) {
			$maincontent .= '
				<div class="patchHead">
					<div class="patchName">' . $patch['manual_name'] . '</div>
					<div class="patchDate">' . $patch['date'] . '</div>
					<div class="clear"></div>
				</div>
				<div class="patchDesc">' . $patch['manual_desc'] . '</div>
			';
		}
		$maincontent .= '</div>';
		root::$_ALL['maintitle'] = 'Patchlog';
		root::$_ALL['maincaption'] = 'Полуавтоматический патчлог';
		root::$_ALL['mainsupport'] = '';
		root::$_ALL['maincontent'] = $maincontent;
		root::$_ALL['backtrace'][] = 'initialized wtf/version';
	}

}