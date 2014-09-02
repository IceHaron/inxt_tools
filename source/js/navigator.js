skeleton = {};
/**
*	
*	Ну конечно же, всякие разные штуки при завершении загрузки страницы
*	
**/
$(document).ready(function() {

	route(get.from, get.to);

/* При клике на регион выводим модальное окно с его системами */
	$('.regButton').click(function() {
		var regID = $(this).attr('data-id');
		$('#shadow').show();
		$('#loading').show();
		getSystems(regID);
	});
	
/* Скрытие облака регионов и выбранных систем при клике на соответствующую кнопку */
	$('.hideRegs').click(function() {
		$('#regionCloud').toggle();
		$('#pathSide').toggle();
	});

	$('select[name=ship]').change(function() {
		$('.timeTable').html('');
		getAttrs($(this).val());
	});

	$(document).on('click', '.systemHolder button[name="from"]', function() {
		var from = $(this).parent().attr('data-name');
		var to = $('#newPath .to').text();
		$('#newPath .from').text(from);
		$('#newPath .newPath').attr('href', '/navigator?from=' + from + '&to=' + to);
		$('#shadow').hide();
		$('.modal').hide();
	});

	$(document).on('click', '.systemHolder button[name="to"]', function() {
		var from = $('#newPath .from').text();
		var to = $(this).parent().attr('data-name');
		$('#newPath .to').text(to);
		$('#newPath .newPath').attr('href', '/navigator?from=' + from + '&to=' + to);
		$('#shadow').hide();
		$('.modal').hide();
	});

	$(document).on('change', '#ssc, #assc, #em, select[id*="module_"], select[id*="implant_"], select[id*="rig_"]', function() {
		calcAttrs();
	});

/* END OF READY */
});

/**
*	
*	Отбираем системы по регионам  записываем их в блок фильтра
*	@param regions - Строка, в которой ID нужных регионов записаны через запятую
*	@return void
*	
**/
function getSystems(regions) {
	var sysinputs = {};
	/* Первым делом получаем список систем для указанных в параметре регионов */
	$.ajax({
		type: 'GET',
		url: '/systemstats/getsystems',
		data: {'regions' : regions},
		dataType: 'json',
		success: function(data) {
			$('#systemSetHolder').empty();
		// Делаем модальное окно
			for (order in data) {
				var sysid = data[order]['id'];
				var sysinfo = data[order];
				var ss = SecurityStanding.format(sysinfo.security);												// Нам нужен СС системы чтобы раскрасить его в нужный цвет
				if (!sysinputs.hasOwnProperty(sysinfo['regname'])) sysinputs[ sysinfo['regname'] ] = '';
				var color = SecurityStanding.paint(ss);
				
				// Моя придумка: помечаем регионы ВХ
				if (sysinfo['name'].search('/J\d{6}/') != -1) sysname = '&lt;WH&gt; ' + sysinfo['name'];
				else sysname = sysinfo['name'];
				
				// Формируем массив HTML-строк, по строке на каждый регион из входящего списка
				sysinputs[ sysinfo['regname'] ] += '<div class="systemHolder" data-name="' + sysname + '" data-id="' + sysid + '" data-regid="' + sysinfo['regionID'] + '"><div class="ss" style="color:' + color + '">' + ss + '</div><span>' + sysname + '</span><button name="from">From</button><button name="to">To</button></div>';
			}
			var fullModalContent = '';
			for (i in sysinputs) {
				var content = sysinputs[i];
				var regionHolder = '<div class="regionHolder"><div><span class="caption">' + i + '</span></div>' + content + '</div>';
				fullModalContent += regionHolder;
			}
			$('#loading').hide();
			$('#systemSetHolder').append(fullModalContent).show();
			
			
			/* Устанавливаем состояние всех чекбоксов в зависимости от выбранных систем */
			var get = $('.selectedSystem')
			if (get[0] != '') {
				var query = {};
				
				$('.regionHolder input').each(function() {
					this.checked = false;																									// Очищаем выделение
				});
				
				get.each(function() {
					elem = $(this).attr('data-id');
					$('.regionHolder input[data-id="' + elem + '"]').each(function() {
						this.checked = true;
					});
				});

			}
			
		},
		complete: function() {																											// После записи систем и расстановки галочек, закрываем прогрессбар
			$('#loading').hide();
		}
	});
}

function route(from, to) {
	from = from ? from : 'Amarr';
	to = to ? to : 'Jita';
	$('#newPath .from').text(from);
	$('#newPath .to').text(to);
	$('#newPath .newPath').attr('href', '/navigator?from=' + from + '&to=' + to);
	var fromTrigger = false;
	var toTrigger = false;
	$.ajax({
		type: 'GET'
	, url: 'map/getsystemsforrouter'
	, data: {'from': from, 'to': to}
	, dataType: 'json'
	, success: function(data) {
			// console.log(data);
			console.timeStamp('Start');
			var dots = data.dots;
			var jumps = data.jumps;
			var routeDots = data.routeDots;
			for (i in dots) {
				var dot = dots[i];
				if (dot.name == from) {
					fromTrigger = true;
				}
				if (dot.name == to) {
					toTrigger = true;
				}
				skeleton[dot.name] = {'regName' : dot.regName, 'security' : SecurityStanding.format(dot.security), 'id' : dot.id};
			}
			if (fromTrigger === true && toTrigger === true) {
				var d = {}; // Длина пути
				var p = {}; // Кратчайший путь
				var r = {}; // Кратчайший путь по регионам
				var u = {}; // Посещенные вершины
				var n = {}; // Вершины для посещения
				var now = '';
				var counter = 0;
				var min = 0;
				var mindot = '';
				d[from] = 0;
				u[from] = d[from];
				now = from;
				for (i in dots) {
					if (dots[i]['name'] != from) {
						d[dots[i]['name']] = 10000;
					}
				}
				while (now != to && counter < 10000) {
					var trigger = false;
					// console.log("Entering to " + now, d[now]);
					delete n[now];
					u[now] = d[now];
					for (i in routeDots[now]) {
						if (d[i] > d[now] + routeDots[now][i] && !u.hasOwnProperty(i)) {
							d[i] = d[now] + routeDots[now][i];
						}
					// console.log("Looking " + i, d[i]);
						if (!u.hasOwnProperty(i)) {
							trigger = true;
							n[i] = d[i];
							min = d[i];
							mindot = i;
							// console.log("Setting to minimum: " + i, d[i]);
						}
					}
					for (i in n) {
						// console.log("Calculating minimum for " + i, d[i], (d[i] <= min && !u.hasOwnProperty(i)) || trigger == false);
						if ((d[i] <= min && !u.hasOwnProperty(i)) || trigger == false) {
							min = d[i];
							mindot = i;
						}
					}
					// console.log("Minimum: " + mindot, min);
					delete n[mindot];
					u[mindot] = min;
					now = mindot;
					counter++;
				}
				console.log(now == to ? 'Found path to destination in ' + counter + ' steps' : counter + ' steps was not enough to find the path');
				now = to;
				p[ now ] = d[now];
				counter = 0;
				var id = skeleton[now]['id'];
				var regname = skeleton[now]['regName'];
				var ss =  skeleton[now]['security'];
				var color = SecurityStanding.paint(ss);
				var graphLink = ',' + now + '_' + ss.replace('.','');
				$('#path').prepend('<div class="pathSystem" data-id="' + id + '" data-name="' + now + '"><div class="ss" style="color:' + color + '">' + ss + '</div>' + now + '<div class="systemMenuButton" data-id="' + id + '" data-name="' + now + '" data-ss="' + ss + '" data-regname="' + regname + '"></div><div class="sysRegHolder"><div class="sysPathRegion">' + regname + '</div></div></div>');
				while (now != from && counter < 10000) {
					for (i in routeDots[now]) {
						if (d[i] < d[now]) {
							p[ i ] = d[i];
							r[skeleton[i]['regName']] = d[i];
							var id = skeleton[i]['id'];
							var regname = skeleton[i]['regName'];
							var ss =  skeleton[i]['security'];
							var color = SecurityStanding.paint(ss);
							graphLink = ',' + i + '_' + ss.replace('.','') + graphLink;
							$('#path').prepend('<div class="pathSystem" data-id="' + id + '" data-name="' + i + '"><div class="ss" style="color:' + color + '">' + ss + '</div>' + i + '<div class="systemMenuButton" data-id="' + id + '" data-name="' + i + '" data-ss="' + ss + '" data-regname="' + regname + '"></div><div class="sysRegHolder"><div class="sysPathRegion">' + regname + '</div></div></div>');
							now = i;
						}
					}
					counter++;
				}
				$('#path').prepend('<p>Проложенный путь <button id="clearPath">Очистить</button></p><a target="blank" href="/systemstats/show?subject=' + graphLink.substr(1) + '"><button>Посмотреть график активности всех систем пути (новое окно)</button></a>');
				console.log('Path is ' + counter + ' jumps long:', p);
				console.timeStamp('Finish');
				// console.log(d,p,u,n,r);
				// if (window.location.search.search('reg') == -1) savePath(r)
				// else 
					savePath(p);
			}
		}
	});
};

function savePath(p) {
	console.log(path = p);
};

function calcTime(mass, agil, warp, acc, dec) {
	$.ajax({
		  type: 'POST'
		, url: 'ajax/calcTime'
		, data: {'route' : JSON.stringify(path), 'mass': mass, 'agil': agil, 'warp': warp, 'acc': acc, 'dec': dec}
		, dataType: 'JSON'
		, success: function(data) {
				var str = '<table class="timeTable">';
				for (i in data.detailed) {
					var jump = data.detailed[i];
					str += '<tr><td>' + jump.jump + '</td><th>' + jump.time.toPrecision(4) + '</th></tr>';
				}
				str += '<tr style="color: yellow"><td>Summary</td><th>' + data.summary.toPrecision(5) + '</th></tr></table><span class="tip">10s added for each jump to compensate interstellar flight</span>';
				$('#timeBlock').html(str);
		}
	});

};

function getAttrs(ship) {
	$.ajax({
		  type: 'GET'
		, url: 'ajax/getShipAttrs'
		, data: {'ship': ship}
		, dataType: 'JSON'
		, success: function(data) {
				$('#group').html(data['group']);
				$('#lowSlots').html(data[12]);
				$('#rigSlots').html(data[1137]);
				$('#rigSize').html(data[1547]);
				$('#shipMass').html(data['mass'] + ' kg');
				$('#shipAgil').html(data[70] * 0.98).attr('data-base', data[70]);
				$('#shipWS').html(data[600] + ' AU/s').attr('data-base', data[600]);
				$('#shipAccel').html(data[600]);
				$('#shipDecel').html((data[600] > 6) ? 2 : (parseFloat(data[600]) / 3).toPrecision(4));
				var rigs = JSON.parse($('#rigs').text());
				var rigStr = '';
				var rigSelect = '';

				for (j in rigs[ data[1547] ]) {
					var rig = rigs[ data[1547] ][j];
					rigStr += '<option value="' + rig['typeID'] + '">' + rig['typeName'] + '</option>';
				}

				for (i = 1; i <= data[1137]; i++) {
					rigSelect += '<select id="rig_' + i + '"><option value="0">Rig ' + i + '</option>' + rigStr + '</select>';
				}

				$('#rigSet').html(rigSelect);

				var modules = JSON.parse($('#modules').text());
				var moduleStr = '';
				var moduleSelect = '';
				var groupNames = {78: 'Reinforced Bulkheads', 762: 'Inertia Stabilizers', 763: 'Nanofiber Structures', 1289: 'Hyperspatial Accelerators'};

				for (group in modules) {
					var module = modules[group];

					moduleStr += '<optgroup label="' + groupNames[group] + '">';

					for (j in module) {
						moduleStr += '<option value="' + module[j]['typeID'] + '">' + module[j]['typeName'] + '</option>';
					}

					moduleStr += '</optgroup>';

				}

				for (i = 1; i <= data[12]; i++) {
					moduleSelect += '<select id="module_' + i + '"><option value="0">module ' + i + '</option>' + moduleStr + '</select>';
				}

				$('#moduleSet').html(moduleSelect);

				calcAttrs();
		}
	});
};

function calcAttrs() {
	var group = parseInt($('#group').text());
	var ssc = parseInt($('#ssc').val());
	var assc = parseInt($('#assc').val());
	var em = parseInt($('#em').val());
	var mass = parseInt($('#shipMass').text());
	var agil = parseFloat($('#shipAgil').attr('data-base'));
	var warp = parseFloat($('#shipWS').attr('data-base'));
	var acc = parseFloat($('#shipAccel').text());
	var dec = parseFloat($('#shipDecel').text());
	var set = new Array();
	var newWarp = warp;
	var newAgil = agil*0.98;
	var capitals = {30: true, 485: true, 513: true, 547: true, 659: true, 883: true, 902: true, 941: true};

	if (!capitals[group]) assc = 0;

	$('select[id*="module_"] option:selected').each(function() {
		set.push(parseFloat($(this).val()));
	});

	$('select[id*="rig_"] option:selected').each(function() {
		set.push(parseFloat($(this).val()));
	});

	$('select[id*="implant_"] option:selected').each(function() {
		set.push(parseFloat($(this).val()));
	});

	$.ajax({
		  type: 'POST'
		, url: 'ajax/calcAttrs'
		, data: {'set': set}
		,dataType: 'JSON'
		, success: function(data) {
				newWarp = (warp + data.warpadd) * data.warpmult;
				newAgil = agil*(1-0.05*em)*(1-0.02*ssc)*(1-0.05*assc)*data.agilmult;
		}
		, complete: function() {
			calcTime(mass, newAgil, newWarp, acc, dec);
			$('#shipAgil').html(newAgil.toPrecision(4));
			$('#shipWS').html(newWarp + ' AU/s');
		}
	})

	// $('select[id*="module_"] option:selected, select[id*="rig_"] option:selected').each(function() {
	// 	var group = $(this).attr('data-bonus');
	// 	bonus[group] = {};
	// 	// console.log($(this).val(), group);
	// 	if (group == 1950) warpAdd += parseFloat($(this).val());
	// 	if (group == 169 || group == 151) {
	// 		if (parseFloat($(this).val()) > 0) agilMult *= 1 + 0.01 * parseFloat($(this).val()) * reductor;
	// 		else {

	// 		}
	// 		// i++;
	// 		// var reductor = Math.pow(0.5,Math.pow((i-1)/2.22292081,2));
	// 	}
	// 	}
	// 	if (group == 624) warpMult *= 1 + 0.01 * parseFloat($(this).val());
	// });

	// $('select[id*="implant_"] option:selected').each(function() {
	// 	var group = $(this).attr('data-bonus');
	// 	if (group == 624) {
	// 		if ($(this).attr('data-stack')) ascendancy *= $(this).attr('data-stack');
	// 	} else if (group == 151) {
	// 		agilMult *= 1 + 0.01 * parseFloat($(this).val());
	// 	}
	// 	console.log(ascendancy);
	// });

	// var newAgil = agil*(1-0.05*em)*(1-0.02*ssc)*(1-0.05*assc)*agilMult;
	// var newWarp = (warp+warpAdd)*warpMult;
}
