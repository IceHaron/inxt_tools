<?

/**
*	
*	Обсуждение
*	
**/

class feedback_index {
	
	public static function init() {
		return new self();
	}
	
	private function feedback_index() {	
		
		root::$_ALL['maintitle'] = 'Обсуждение';
		root::$_ALL['maincaption'] = 'Обсуждение проекта';
		root::$_ALL['mainsupport'] = '';
		root::$_ALL['maincontent'] = '';
		root::$_ALL['backtrace'][] = 'initialized feedback/index';
	}
	
}