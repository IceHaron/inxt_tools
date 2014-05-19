$(document).ready(function() {
	var canvas=document.getElementById("map");
	var cxt=canvas.getContext("2d");
	var width = canvas.width;
	var height = canvas.height;
	var padding = 50;
	var depth = 500
	var map = JSON.parse($('#strForMap').text());
	var dots = map.dots;
	var jumps = map.jumps;
	var path = {};
	// var dots = {};
	// var jumps = {};
	skeleton = {};
	var pi = Math.PI;
	var zenit = 0;
	var azimut = 0;
	var maxx = minx = parseFloat(dots[0].pos_x);
	var maxy = miny = parseFloat(dots[0].pos_y);
	var maxz = minz = parseFloat(dots[0].pos_z);
	var coords = {
		//   'a':{'x':-100,'y':100,'z':100}
		// , 'b':{'x':100,'y':100,'z':100}
		// , 'c':{'x':100,'y':-100,'z':100}
		// , 'd':{'x':-100,'y':-100,'z':100}
		// , 'e':{'x':-100,'y':100,'z':-100}
		// , 'f':{'x':100,'y':100,'z':-100}
		// , 'g':{'x':100,'y':-100,'z':-100}
		// , 'h':{'x':-100,'y':-100,'z':-100}
		// , 'z':{'x':0,'y':0,'z':0}
		// , 'width':{'x':200,'y':0,'z':0}
		// , 'height':{'x':0,'y':200,'z':0}
		// , 'length':{'x':0,'y':0,'z':200}
	};
	var visibleDots = {
		//   'a':{'x':-100,'y':100,'z':100}
		// , 'b':{'x':100,'y':100,'z':100}
		// , 'c':{'x':100,'y':-100,'z':100}
		// , 'd':{'x':-100,'y':-100,'z':100}
		// , 'e':{'x':-100,'y':100,'z':-100}
		// , 'f':{'x':100,'y':100,'z':-100}
		// , 'g':{'x':100,'y':-100,'z':-100}
		// , 'h':{'x':-100,'y':-100,'z':-100}
		// , 'z':{'x':0,'y':0,'z':0}
		// , 'width':{'x':200,'y':0,'z':0}
		// , 'height':{'x':0,'y':200,'z':0}
		// , 'length':{'x':0,'y':0,'z':200}
	};
	var currentRegion = $('#mapRegion option:selected').val() != 0 ? $('#mapRegion option:selected').html() : '';
	for (i in dots) {
		var dot = dots[i];
		coords[dot.name] = {};
		visibleDots[dot.name] = {};
		if (dot.regName == currentRegion || currentRegion == '') {
			if (parseFloat(dot.pos_x) < minx) minx = parseFloat(dot.pos_x);
			if (parseFloat(dot.pos_x) > maxx) maxx = parseFloat(dot.pos_x);
			if (parseFloat(dot.pos_y) < miny) miny = parseFloat(dot.pos_y);
			if (parseFloat(dot.pos_y) > maxy) maxy = parseFloat(dot.pos_y);
			if (parseFloat(dot.pos_z) < minz) minz = parseFloat(dot.pos_z);
			if (parseFloat(dot.pos_z) > maxz) maxz = parseFloat(dot.pos_z);
		}
		skeleton[dot.name] = {'regName' : dot.regName, 'security' : SecurityStanding.format(dot.security), 'id' : dot.id};
	}
	var divx = maxx - minx;
	var divy = maxy - miny;
	var divz = maxz - minz;
	var scaleX = (width-padding*2) / divx;
	var scaleY = (height-padding*2) / divy;
	var scaleZ = (depth-padding*2) / divz;
	calc();
	// if (localStorage.getItem('from') !== null)
		// $('#fromStar').val(localStorage.getItem('from')).attr('data-id', skeleton[localStorage.getItem('from')]['id']).css('background-color', 'lime');
	// if (localStorage.getItem('to') !== null)
		// $('#toStar').val(localStorage.getItem('to')).attr('data-id', skeleton[localStorage.getItem('to')]['id']).css('background-color', 'lime');
	testRouter();
	if (get.hasOwnProperty('from') && get.hasOwnProperty('to')) route(get.from, get.to);
	else {
		draw();
		allowSelect();
	}

	$(canvas).mousedown(function(e) {
		$('.star').remove();
		var pos = $(this).offset();
		$('.startx').html(e.pageX-pos.left);
		$('.starty').html(e.pageY-pos.top);
		$(canvas).bind('mousemove', drag);
		$(window).bind('mouseup', function(ev) {
		});
	});

	$(canvas).mouseup(function() {
		$(canvas).unbind('mousemove');
		$(window).unbind('mouseup');
		allowSelect();
	});

	$(document).on('click', '.star', function() {
		if (!get.hasOwnProperty('reg')) {
			var search = '';
			for (i in get) search += '&'+i+'='+get[i];
			var newLoc = window.location.pathname + '?reg=' + escape($(this).attr('data-name')) + search;
			window.location = newLoc;
		} else {
			var name = $(this).attr('data-name');
			$('.star[data-name!="' + name + '"]').hide();
			var fromPrefix = '';
			var toPrefix = '';
			if ($('#fromStar').val() == name && $('#fromStar').attr('data-id') !== undefined) toPrefix = 'not_';
			if ($('#toStar').val() == name && $('#toStar').attr('data-id') !== undefined) fromPrefix = 'not_';
			$(this).append('<div class="makePath"><div class="' + fromPrefix + 'fromHere">Отсюда</div><div class="' + toPrefix + 'toHere">Сюда</div></div>');
			testRouter();
		}
	});

	$('#drawMap').click(function() {
		var search = '';
		for (i in get) if (i != 'reg') search += '&' + i + '=' + get[i];
		if ($('#mapRegion option:selected').val() != '0') window.location = window.location.pathname + "?reg=" + escape($('#mapRegion option:selected').html()) + search;
		else window.location = window.location.pathname + (search != '' ? '?' + search.substr(1) : '');
	});

	$('#resetMap').click (function() {
		var search = '';
		for (i in get) if (i != 'reg') search += '&' + i + '=' + get[i];
			window.location = window.location.pathname + (search != '' ? '?' + search.substr(1) : '');
	})

	$(document).on('mouseenter', '.star', function() {
		var starName = $(this).attr('data-name');
		$(this).append('<span class="starName">' + starName + '</span>');
	});

	$(document).on('mouseleave', '.star', function() {
		$(this).children('.starName').remove();
		$(this).children('.makePath').remove();
		$('.star').show();
	});

	$(document).on('mouseenter', '.pathStar', function() {
		var name = $(this).attr('data-name');
		$('.star[data-name="' + name + '"]').css('opacity', '1').append('<span class="starName">' + name + '</span>');
	});
	$(document).on('mouseleave', '.pathStar', function() {
		var name = $(this).attr('data-name');
		$('.star[data-name="' + name + '"]').css('opacity', '').children('.starName').remove();
	});

	$(document).on('click', '.fromHere', function() {
		var name = $(this).parent().parent().attr('data-name');
		$('#fromStar').val(name).attr('data-id', skeleton[name]['id']).css('background-color', 'lime');
	});

	$(document).on('click', '.toHere', function() {
		var name = $(this).parent().parent().attr('data-name');
		$('#toStar').val(name).attr('data-id', skeleton[name]['id']).css('background-color', 'lime');
	});

/* Поиск систем */
	$('#fromStar, #toStar').keyup(function(key) {
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
		var referrer = $(this).attr('id');
		var pass = true;
		for (i in noAcceptKeys) {
			if (noAcceptKeys[i] === key.keyCode) pass = false
		}
		if (pass) $(this).css('background-color', 'lightpink').removeAttr('data-id');
		if (pass && $(this).val().length > 2) {
			$('#systemSearchVariants').html('<img width="30" src="/source/img/loading-dark.gif">').show();
			$.ajax({
				type: 'GET'
			, url: 'systemstats/searchsystems'
			, data: {'search' : $(this).val()}
			, dataType: 'json'
			, success: function(data) {
					$('#systemSearchVariants').html('').show().attr('data-referrer', referrer);
					for (i in data) {
						var variant = data[i];
						if ($('.selectedStar[data-name="' + variant.name + '"]').length == 0)
							$('#systemSearchVariants').append('<div class="ssVariant" data-regid="' + variant.regionID + '" data-id="' + variant.id + '" data-name="' + variant.name + '"><span style="color: ' + SecurityStanding.paint(variant.security) + '" class="ssVariantSS">' + SecurityStanding.format(variant.security) + '</span><span class="ssVariantStar">' + variant.name + '</span><span class="ssVariantReg">' + variant.regionName + '</span></div>');
						else
							$('#systemSearchVariants').append('<div class="ssVariantInactive" data-regid="' + variant.regionID + '" data-id="' + variant.id + '" data-name="' + variant.name + '"><span style="color: ' + SecurityStanding.paint(variant.security) + '" class="ssVariantSS">' + SecurityStanding.format(variant.security) + '</span><span class="ssVariantStar">' + variant.name + '</span><span class="ssVariantReg">' + variant.regionName + '</span></div>');
					}
				}
			, complete: function(data) {
					if (data.responseText == 'NULL') $('#systemSearchVariants').html('Nothing found').show();
				}
			});
		}
	});

	$('#fromStar, #toStar').click(function() {
		$('#systemSearchVariants').hide();
		if ($('.ssVariant').length > 0) $('#systemSearchVariants[data-referrer="' + $(this).attr('id') + '"]').show();
	});

	$('#fromStar, #toStar').change(function() {
		var id = $(this).attr('data-id');
		var name = $(this).val();
		testRouter();
	});

	$(document).on('click', '.ssVariant', function() {
		var name = $(this).attr('data-name');
		var id = $(this).attr('data-id');
		var referrer = $(this).parent().attr('data-referrer');
		$('#' + referrer).val(name).attr('data-id', id).css('background-color', 'lime');
		testRouter();
	});

	function route(from, to) {
		from = from ? from : 'Amarr';
		to = to ? to : 'Jita';
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
						$('#path').prepend('<div class="pathStar" data-id="' + id + '" data-name="' + now + '"><div class="ss" style="color:' + color + '">' + ss + '</div>' + now + '<div class="starMenuButton" data-id="' + id + '" data-name="' + now + '" data-ss="' + ss + '" data-regname="' + regname + '"></div><div class="sysRegHolder"><div class="sysPathRegion">' + regname + '</div></div></div>');
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
									$('#path').prepend('<div class="pathStar" data-id="' + id + '" data-name="' + i + '"><div class="ss" style="color:' + color + '">' + ss + '</div>' + i + '<div class="starMenuButton" data-id="' + id + '" data-name="' + i + '" data-ss="' + ss + '" data-regname="' + regname + '"></div><div class="sysRegHolder"><div class="sysPathRegion">' + regname + '</div></div></div>');
									now = i;
								}
							}
							counter++;
						}
						$('#path').prepend('<p>Проложенный путь:</p><a target="blank" href="/systemstats/show?subject=' + graphLink.substr(1) + '"><button>Посмотреть график активности всех систем пути (новое окно)</button></a>');
						console.log('Path is ' + counter + ' jumps long:', p);
						console.timeStamp('Finish');
						for (var i = 0; i < localStorage.length; i++) {
							key = localStorage.key(i);
							if (key.search('mark_') !== -1) {
								var star = JSON.parse(localStorage.getItem(key));
								var color = SecurityStanding.paint(star.ss);
								$('#path').prepend('<div class="pathStar" data-id="' + star.id + '" data-name="' + i + '"><div class="ss" style="color:' + color + '">' + star.ss + '</div>' + star.name + '<div class="starMenuButton" data-id="' + star.id + '" data-name="' + star.name + '" data-ss="' + star.ss + '" data-regname="' + star.regname + '"></div><div class="sysRegHolder"><div class="sysPathRegion">' + star.regname + '</div></div></div>');
							}
						}
						$('#path').prepend('<p>Отмеченные системы:</p>');
						// console.log(d,p,u,n,r);
						if (window.location.search.search('reg') == -1) savePath(r)
						else savePath(p);
				} else return false;
			}
		});
	}

	function allowSelect() {
		for (i in visibleDots) {
			var dot = visibleDots[i];
			var posleft = dot["x"]+width/2-10;
			var postop = -dot["y"]+height/2-10;
			if (postop > -10 && posleft > -10 && postop < height-15 && posleft < width-15) {
				$('.interaction').append('<div class="star" data-name="' + i + '"><img src="/source/img/starCircle.png"></div>');
				$('.star[data-name="' + i + '"]').css({'margin-left':posleft, 'margin-top':postop});
			}
		}
	}

	function drag(ev) {
		var pos = $(this).offset();
		// $('.x').html(ev.pageX-pos.left-parseInt($('.startx').html()));
		// $('.y').html(ev.pageY-pos.top-parseInt($('.starty').html()));
		azimut += (ev.pageX-pos.left-parseInt($('.startx').html()))/500;
		zenit += (ev.pageY-pos.top-parseInt($('.starty').html()))/500;
		draw();
		$('.startx').html(ev.pageX-pos.left);
		$('.starty').html(ev.pageY-pos.top);
	}

	function calc() {
		for (i in dots) {
			var dot = dots[i];
			var x = (parseFloat(dot.pos_x) - minx - divx / 2) * scaleX;
			var y = (parseFloat(dot.pos_y) - miny - divy / 2) * scaleY;
			var z = (parseFloat(dot.pos_z) - minz - divz / 2) * scaleZ;
			coords[dot.name]["x"] = x;
			visibleDots[dot.name]["x"] = x;
			coords[dot.name]["y"] = y;
			visibleDots[dot.name]["y"] = y;
			coords[dot.name]["z"] = z;
		}
	}

	function draw() {
		canvas.width = width;
		cxt.fillStyle = "black";
		cxt.fillRect(0, 0, width, height);
		cxt.fillStyle = 'white';
		cxt.strokeStyle = '#505050';
		cxt.font = "normal 8pt Sans-Serif";
		for (i in jumps) {
			if (!(localStorage.getItem('mark_' + skeleton[jumps[i].fromName]['id']) && localStorage.getItem('mark_' + skeleton[jumps[i].toName]['id'])) && !(path.hasOwnProperty(jumps[i].fromName) && path.hasOwnProperty(jumps[i].toName))) {
				drawJump(jumps[i]);
				cxt.strokeStyle = '#505050';
			}
		}
		for (i in coords) {
			if (!localStorage.getItem('mark_' + skeleton[i]['id']) && !path.hasOwnProperty(i)) {
				drawStar(coords[i]);
				cxt.fillStyle = 'white';
			}
		}
		cxt.font = "bold 10pt Sans-Serif";
		for (i in jumps) {
			if (path.hasOwnProperty(jumps[i].fromName) && path.hasOwnProperty(jumps[i].toName)) {
				cxt.strokeStyle = 'yellow';
				drawJump(jumps[i]);
			} else if (localStorage.getItem('mark_' + skeleton[jumps[i].fromName]['id']) && localStorage.getItem('mark_' + skeleton[jumps[i].toName]['id'])) {
				cxt.strokeStyle = 'lime';
				drawJump(jumps[i]);
			}
		}
		for (i in coords) {
			if (path.hasOwnProperty(i)) {
				cxt.fillStyle = 'yellow';
				drawStar(coords[i]);
			} else if (localStorage.getItem('mark_' + skeleton[i]['id'])) {
				cxt.fillStyle = 'lime';
				drawStar(coords[i]);
			}
		}
	}

	function drawJump(jump) {
		var newfromx = coords[jump.fromName]["x"]*Math.cos(azimut) - coords[jump.fromName]["y"]*Math.sin(azimut);
		var newfromy = coords[jump.fromName]["x"]*Math.cos(zenit)*Math.sin(azimut) + coords[jump.fromName]["y"]*Math.cos(zenit)*Math.cos(azimut) - coords[jump.fromName]["z"]*Math.sin(zenit);
		var newfromz = coords[jump.fromName]["x"]*Math.sin(zenit)*Math.sin(azimut) + coords[jump.fromName]["y"]*Math.sin(zenit)*Math.cos(azimut) + coords[jump.fromName]["z"]*Math.cos(zenit);
		var fromPerspective = (newfromz + divz * scaleZ) / divz / scaleZ / 10 + 0.9;
		var newtox = coords[jump.toName]["x"]*Math.cos(azimut) - coords[jump.toName]["y"]*Math.sin(azimut);
		var newtoy = coords[jump.toName]["x"]*Math.cos(zenit)*Math.sin(azimut) + coords[jump.toName]["y"]*Math.cos(zenit)*Math.cos(azimut) - coords[jump.toName]["z"]*Math.sin(zenit);
		var newtoz = coords[jump.toName]["x"]*Math.sin(zenit)*Math.sin(azimut) + coords[jump.toName]["y"]*Math.sin(zenit)*Math.cos(azimut) + coords[jump.toName]["z"]*Math.cos(zenit);
		var toPerspective = (newtoz + divz * scaleZ) / divz / scaleZ / 10 + 0.9;
		cxt.beginPath();
		cxt.moveTo(newfromx*fromPerspective+width/2+2, -newfromy*fromPerspective+height/2+2);
		cxt.lineTo(newtox*toPerspective+width/2+2, -newtoy*toPerspective+height/2+2);
		cxt.closePath();
		cxt.stroke();
	};

	function drawStar(dot) {
		var newx = dot["x"]*Math.cos(azimut) - dot["y"]*Math.sin(azimut);
		var newy = dot["x"]*Math.cos(zenit)*Math.sin(azimut) + dot["y"]*Math.cos(zenit)*Math.cos(azimut) - dot["z"]*Math.sin(zenit);
		var newz = dot["x"]*Math.sin(zenit)*Math.sin(azimut) + dot["y"]*Math.sin(zenit)*Math.cos(azimut) + dot["z"]*Math.cos(zenit);
		var perspective = (newz + divz * scaleZ) / divz / scaleZ / 10 + 0.9;
		cxt.fillRect(newx*perspective+width/2, -newy*perspective+height/2, 3, 3);
		cxt.fillText(i,newx*perspective+width/2, -newy*perspective+height/2-1);
		visibleDots[i]["x"] = newx*perspective;
		visibleDots[i]["y"] = newy*perspective;
	};

	function savePath(p) {
		console.log(path = p);
		draw();
		allowSelect();
	}

	function testRouter() {
		if ($('#fromStar').val() != '' && $('#fromStar').attr('data-id') !== undefined) localStorage.setItem('from', $('#fromStar').val());
			else localStorage.removeItem('from', null);
		if ($('#toStar').val() != '' && $('#toStar').attr('data-id') !== undefined) localStorage.setItem('to', $('#toStar').val());
			else localStorage.removeItem('to', null);
		if($('#fromStar').val() != '' && $('#fromStar').attr('data-id') !== undefined && $('#toStar').val() != '' && $('#toStar').attr('data-id') !== undefined)
			$('#submitPath').removeAttr('disabled');
		else $('#submitPath').attr('disabled', true);
	}

});