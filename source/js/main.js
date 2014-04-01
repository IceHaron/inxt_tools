
/**
*	
*	Ну конечно же, всякие разные штуки при завершении загрузки страницы
*	
**/
$(document).ready(function() {

/* При клике на тень скрываем ее и все модальные окна */
	$('#shadow').click(function() {
		$(this).hide();
		$('.modal').hide();
	});

/* Клик на заголовке скрывает/раскрывает фильтр и что-то еще делает, пока не придумал */
	$('#maincaption').click(function() {
	  $('#mainsupport').toggle();																									// Фильтр пока существует только в библиотеке, в этот блок думаю напихать всяких разных удобных штук
	});
  
/* Перехватчик клика на логофф, кнопка должна что-то делать хитрое, пока просто перенаправляет на страничку логоффа */
  $('#logoff').click(function() {
    window.location = '/auth/logoff';
  });
	
/* Работа с табличкой TODO */

	$('.DONE').parent().hide();																										// Скрываем то, что я уже сделал, чтоб не мешались
	
	$('#todo tbody tr').hover(function() {
		$(this).children('td').animate({'opacity': '1'}, 100);
	},
	function() {
		if ($(this).children('td').eq(2).attr('class') == 'UNDONE-right')	$(this).children('td').animate({'opacity': '0.7'}, 100);
		else $(this).children('td').animate({'opacity': '0.3'}, 100);
	});
	
/* End of READY() */
});


/**
*	
*	Функция логина от uLogin
*	
**/
function login(token){
	// Отправляем AJAX-запрос к ним
	$.getJSON("//ulogin.ru/token.php?host=" + encodeURIComponent(location.toString()) + "&token=" + token + "&callback=?",
	function(data){
		data=$.parseJSON(data.toString());
		if(!data.error){
			document.cookie = 'uid=' + data.uid;
			window.location.reload(); 																								// Костыль
		}
	});
}

/**
*	
*	Объект для работы с датой
*	
**/
var myDate = {

/**
*	
*	Переформатирование даты в строку
* @param date - дата в своем формате
* @param mode - мод часовой/дневной/месячный, указывает формат даты
*	@return res - дата в формате строки
*	
**/
	morph: function(date, mode) {
		var day = this.zerofill(date.getDate().toString(), 2);
		var mon = this.zerofill(parseInt(date.getMonth().toString()) + 1, 2);
		var year = date.getFullYear().toString();
		var hour = this.zerofill(date.getHours().toString(), 2);
		var min = this.zerofill(date.getMinutes().toString(), 2);
		var res = '';
		if (mode == 'hourly') res = day + '-' + mon + ' ' + hour + ':' + min;
		if (mode == 'daily') res = day + '-' + mon + '-' + year;
		if (hour == '13') res += ' [DT]';
		return res;
	},
	
/**
*	
*	Дополнение числа нулями с левой стороны
* @param num - число
* @param len - нужная длина строки
*	@return outStr - число в строчном формате, дополненное слева нулями до нужной длины строки
*	
**/
	zerofill: function(num, len) {
		var numLen = (num+'').length;
		var outStr = '';
		for (i = 0; i < len-numLen; i++) {
			outStr += '0';
		}
		outStr += num;
		return outStr;
	}
	
};
