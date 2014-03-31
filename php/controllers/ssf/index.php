<?

/**
*	
*	Тестовый класс, воротим здесь что захотим и всякие штуки тестируем именно тут.
*	Заодно это шаблон для создания других классов.
*	
**/

class ssf_index {
	
	public static function init() {
		return new self();
	}
	
	private function ssf_index() {
		global $GAMINAS;
		
		$GAMINAS['maincaption'] = 'EVE Smart Star Filters';
		$GAMINAS['mainsupport'] = 'Содержание вспомогательного блока';
		$GAMINAS['maincontent'] = 'Содержание центрального блока';
		$GAMINAS['backtrace'][] = 'initialized ssf/index';
	}

}