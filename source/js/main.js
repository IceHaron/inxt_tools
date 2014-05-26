/* Определяем глобальные переменные */
get = getGet();   // Получаем GET-запрос
countdown = 500;  // Устанавливаем отсчет времени чтоб не флудить AJAX`ами
searching = 0;    // Устаналиваем переключатель поискового слова
somethingChanged = false;  // Устанавливаем переключатель изменения состояния

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
	
/* Поиск систем */
	$('.systemSearch').keyup(function(key) {
		var noAcceptKeys = new Array(
			9		// Tab
		, 16	// Shift
		, 17	// Ctrl
		, 18	// Alt
		, 37	// Left
		, 38	// Up
		, 39	// Right
		, 40	// Down
		, 116	// F5
		);
		var pass = true;
		for (i in noAcceptKeys) {
			if (noAcceptKeys[i] === key.keyCode) pass = false
		}
		if (pass) {
			countdown = 500;
			searching++;
			if ($(this).val().length > 2) systemSearch($(this).val());
		}
	});
	
/* При выборе варианта в поиске скрываем список вариантов и добавляем выбранную систему в список */
	$(document).on('click', '.ssVariant', function() {
		var name = $(this).attr('data-name');
		var id = $(this).attr('data-id');
		var regid = $(this).attr('data-regid');
		var regname = $(this).children('.ssVariantReg').text();
		var ss = $(this).children('.ssVariantSS').text();
		
		$('.fromSearch').append('<div class="selectedSystem" data-regid="' + regid + '" data-id="' + id + '" data-name="' + name + '"><div class="ss" style="color:' + SecurityStanding.paint(ss) + '">' + ss + '</div>' + name + '<img class="deselectSystem" src="/source/img/delete.png"><div class="sysRegHolder"><div class="sysRegion">' + regname + '</div></div></div>');
		
		$('#systemSearchVariants').hide();
		$(this).attr('class', 'ssVariantInactive');
		somethingChanged = true;
	});
	
/* Скрываем список найденных систем при клике в другое место */
	$(document).click(function(t) {
		if ($(t.target).attr('class') != 'ssVariant'
			&& $(t.target).attr('class') != 'ssVariantSystem'
			&& $(t.target).attr('class') != 'ssVariantSS'
			&& $(t.target).attr('class') != 'ssVariantReg'
			&& $(t.target).attr('class') != 'systemSearch'
			&& $(t.target).attr('id') != 'systemSearch'
			&& $(t.target).attr('id') != 'fromSystem'
			&& $(t.target).attr('id') != 'toSystem'
			&& $(t.target).attr('id') != 'systemSearchVariants'
			)
				$('#systemSearchVariants').hide();
		if ($(t.target).attr('class') == 'systemSearch' && $('.ssVariant').length > 0) $('#systemSearchVariants').show();
		if ($(t.target).attr('class') != 'systemMenuButton' && $(t.target).attr('id') != 'systemMenu' && $(t.target).parent().attr('id') != 'systemMenu')
			$('#systemMenu').hide();
	});

	$(document).on('click', '.systemMenuButton', function () {
		var id = $(this).attr('data-id');
		var name = $(this).attr('data-name');
		var ss = $(this).attr('data-ss');
		var regname = $(this).attr('data-regname');
		var elem = $('#systemMenu');
		var elemW = elem.width();
		var left = $(this).offset().left;
		var top = $(this).offset().top;
		elem.css({'left':left - elemW + 16,'top':top - 4}).show();
		elem.attr('data-id', id);
		elem.attr('data-name', name);
		elem.attr('data-ss', ss);
		elem.attr('data-regname', regname);
		if (localStorage.getItem('mark_' + id)) {
			$('#markOnMap').hide();
			$('#dismarkOnMap').show();
		} else {
			$('#markOnMap').show();
			$('#dismarkOnMap').hide();
		}
	});

	$('#markOnMap').click(function() {
		var parent = $(this).parent();
		var id = parent.attr('data-id');
		var name = parent.attr('data-name');
		var ss = parent.attr('data-ss');
		var regname = parent.attr('data-regname');
		var color = SecurityStanding.paint(ss);
		localStorage.setItem('mark_' + id, JSON.stringify({'id':id,'name':name,'ss':ss,'regname':regname}));
		$('#markedSystems').append('<div class="pathSystem" data-id="' + id + '" data-name="' + name + '"><div class="ss" style="color:' + color + '">' + ss + '</div>' + name + '<div class="systemMenuButton" data-id="' + id + '" data-name="' + name + '" data-ss="' + ss + '" data-regname="' + regname + '"></div><div class="sysRegHolder"><div class="sysPathRegion">' + regname + '</div></div></div>');
		$('#markedSystems p').show();
		$('#markOnMap').hide();
		$('#dismarkOnMap').show();
		parent.hide();
	});
	
	$('#dismarkOnMap').click(function() {
		var parent = $(this).parent();
		var id = $(this).parent().attr('data-id');
		localStorage.removeItem('mark_' + id);
		$('#markedSystems .pathSystem[data-id="' + id + '"]').remove();
		if ($('#markedSystems .pathSystem').length == 0) $('#markedSystems p').hide();
		$('#markOnMap').show();
		$('#dismarkOnMap').hide();
		parent.hide();
	});
	
/* End of READY() */
});

function systemSearch(what) {
	var number = searching;
	setTimeout(function() {
		countdown -= 100;
		if (number == searching) {
			if (countdown <= 0) {
				$('#systemSearchVariants').html('<img width="30" src="/source/img/loading-dark.gif">').show();
				$.ajax({
					type: 'GET'
				, url: 'searchsystems'
				, data: {'search' : what}
				, dataType: 'json'
				, success: function(data) {
						$('#systemSearchVariants').html('').show();
						for (i in data) {
							var variant = data[i];
							if ($('.selectedSystem[data-name="' + variant.name + '"]').length == 0)
								$('#systemSearchVariants').append('<div class="ssVariant" data-regid="' + variant.regionID + '" data-id="' + variant.id + '" data-name="' + variant.name + '"><span style="color: ' + SecurityStanding.paint(variant.security) + '" class="ssVariantSS">' + SecurityStanding.format(variant.security) + '</span><span class="ssVariantSystem">' + variant.name + '</span><span class="ssVariantReg">' + variant.regionName + '</span></div>');
							else
								$('#systemSearchVariants').append('<div class="ssVariantInactive" data-regid="' + variant.regionID + '" data-id="' + variant.id + '" data-name="' + variant.name + '"><span style="color: ' + SecurityStanding.paint(variant.security) + '" class="ssVariantSS">' + SecurityStanding.format(variant.security) + '</span><span class="ssVariantSystem">' + variant.name + '</span><span class="ssVariantReg">' + variant.regionName + '</span></div>');
						}
						searching = 0;
						countdown = 500;
					}
				, complete: function(data) {
						if (data.responseText == 'NULL') $('#systemSearchVariants').html('Nothing found').show();
					}
				});
			} else systemSearch(what);
		}
	}, 100);
}


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
			window.location.reload();                             // Костыль
		}
	});
}

/**
*	
*	Функция получения GET-запроса
*	
**/
function getGet() {
	if (window.location.search != '') {
		var path = window.location.search.replace(/\?/, '').split('&');
		var result = {};
		for (i in path) {
			var a = path[i].split('=');
			result[ a[0] ] = a[1];
		}
		return result;
	} else return {};
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

/**
*	
*	Объект для работы с секьюром
*	
**/
var SecurityStanding = {

/**
*	
*	Перекраска СС системы для примерного соответствия цвету в игре.
* @param (string/float) - СС системы
*	@return color - цвет окраса СС в CSS-формате
*	
**/
	paint : function(ss) {
		var color = 'red';
		var numSS = this.format(ss);
		if (numSS == 1) color = 'skyblue';
		if (numSS <= 0.9 && numSS > 0.6) color = 'green';
		if (numSS <= 0.6 && numSS > 0.4) color = 'yellow';
		if (numSS <= 0.4 && numSS > 0.0) color = 'orange';

		return color;
	},

/**
*	
*	Переформатирование СС системы в нужный формат: -0.0
* @param (string/float) - СС системы в грязном виде
*	@return formatted - СС системы в нужном формате
*	
**/
	format : function (ss) {
		var formatted = parseFloat(ss).toFixed(1)
		
		return formatted;
	}
};