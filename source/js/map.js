$(document).ready(function() {
	var canvas = $('#map');
	// var canvas=document.getElementById("map");
	// var cxt=canvas.getContext("2d");
	// var width = canvas.width;
	// var height = canvas.height;
	var width = 890;
	var height = 650;
	var padding = 50;
	var depth = 500
	var map = JSON.parse($('#strForMap').text());
	var dots = map.dots;
	var jumps = map.jumps;
	// var dots = {};
	// var jumps = {};
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
	for (i in dots) {
		var dot = dots[i];
		coords[dot.name] = {};
		if (parseFloat(dot.pos_x) < minx) minx = parseFloat(dot.pos_x);
		if (parseFloat(dot.pos_x) > maxx) maxx = parseFloat(dot.pos_x);
		if (parseFloat(dot.pos_y) < miny) miny = parseFloat(dot.pos_y);
		if (parseFloat(dot.pos_y) > maxy) maxy = parseFloat(dot.pos_y);
		if (parseFloat(dot.pos_z) < minz) minz = parseFloat(dot.pos_z);
		if (parseFloat(dot.pos_z) > maxz) maxz = parseFloat(dot.pos_z);
	}
	var scaleX = (width-padding*2) / (maxx - minx);
	var scaleY = (height-padding*2) / (maxy - miny);
	var scaleZ = (depth-padding*2) / (maxz - minz);
	calc();
	canvas.mousedown(function(e) {
		var pos = $(this).offset();
		$('.startx').html(e.pageX-pos.left);
		$('.starty').html(e.pageY-pos.top);
		$(canvas).bind('mousemove', function(ev) {
			var pos = $(this).offset();
			// $('.x').html(ev.pageX-pos.left-parseInt($('.startx').html()));
			// $('.y').html(ev.pageY-pos.top-parseInt($('.starty').html()));
			azimut += (ev.pageX-pos.left-parseInt($('.startx').html()))/500;
			zenit += (ev.pageY-pos.top-parseInt($('.starty').html()))/500;
			draw();
			$('.startx').html(ev.pageX-pos.left);
			$('.starty').html(ev.pageY-pos.top);
		});
		$(window).bind('mouseup', function(ev) {
		});
	});
	canvas.mouseup(function() {
		$(canvas).unbind('mousemove');
		$(window).unbind('mouseup');
	});

	// setInterval(function() {
	// 	azimut += 0.05;
	// 	zenit += 0.05;
		draw();
	// }, 100);

	function calc() {
		for (i in dots) {
			var dot = dots[i];
			var x = (parseFloat(dot.pos_x) + Math.abs(minx)) * scaleX + padding - width/2;
			var y = (parseFloat(dot.pos_y) + Math.abs(miny)) * scaleY + padding - height/2;
			var z = (parseFloat(dot.pos_z) + Math.abs(minz)) * scaleZ + padding - depth/2;
			coords[dot.name]["x"] = x;
			coords[dot.name]["y"] = y;
			coords[dot.name]["z"] = z;
		}
	}

	function draw() {
		// canvas.width = canvas.width;
		// cxt.fillStyle = "black";
		// cxt.fillRect(0, 0, canvas.width, canvas.height);
		// cxt.fillStyle = 'white';
		// cxt.strokeStyle = 'grey';
		// cxt.font = "normal 8pt Sans-Serif";
		for (i in jumps) {
			var jump = jumps[i];
			var newfromx = coords[jump.fromName]["x"]*Math.cos(azimut) - coords[jump.fromName]["y"]*Math.sin(azimut);
			var newfromy = coords[jump.fromName]["x"]*Math.cos(zenit)*Math.sin(azimut) + coords[jump.fromName]["y"]*Math.cos(zenit)*Math.cos(azimut) - coords[jump.fromName]["z"]*Math.sin(zenit);
			var newtox = coords[jump.toName]["x"]*Math.cos(azimut) - coords[jump.toName]["y"]*Math.sin(azimut);
			var newtoy = coords[jump.toName]["x"]*Math.cos(zenit)*Math.sin(azimut) + coords[jump.toName]["y"]*Math.cos(zenit)*Math.cos(azimut) - coords[jump.toName]["z"]*Math.sin(zenit);
			// cxt.beginPath();
			// cxt.moveTo(newfromx+canvas.width/2+2, -newfromy+canvas.height/2+2);
			// cxt.lineTo(newtox+canvas.width/2+2, -newtoy+canvas.height/2+2);
			// cxt.closePath();
			// cxt.stroke();
		}
		for (i in coords) {
			var dot = coords[i];
			var multiplier = dot["z"] > 0 ? 1.1 : 0.9;
			var newx = Math.round(dot["x"]*Math.cos(azimut) - dot["y"]*Math.sin(azimut) + width/2);
			var newy = Math.round(dot["x"]*Math.cos(zenit)*Math.sin(azimut) + dot["y"]*Math.cos(zenit)*Math.cos(azimut) - dot["z"]*Math.sin(zenit) + height/2);
			// var newz = dot["x"]*Math.sin(zenit)*Math.sin(azimut) + dot["y"]*Math.sin(zenit)*Math.cos(azimut) + dot["z"]*Math.cos(zenit);
			// console.log(i,dot,newx,newy);
			if ($('.star[data-name="' + i + '"]').length == 0) {
				canvas.append('<div class="star" data-name="' + i + '" style="margin-left:' + newx + 'px;margin-top:' + newy + 'px;"></div>');
				canvas.append('<span class="starName" data-name="' + i + '" style="margin-left:' + (newx + 4) + 'px;margin-top:' + (newy - 6) + 'px;">' + i + '</span>');
			} else {
				var oldX = $('.star[data-name="' + i + '"]').css('margin-left');
				if (oldX != newx) {
					$('.star[data-name="' + i + '"]').css({'margin-left': newx, 'margin-top':newy});
					$('.starName[data-name="' + i + '"]').css({'margin-left': newx+4, 'margin-top':newy-6});
				}
			}
			// cxt.fillRect(newx+canvas.width/2, -newy+canvas.height/2, 4, 4);
			// cxt.fillText(i,newx+canvas.width/2, -newy+canvas.height/2-1);

			// cxt.beginPath();
			// cxt.moveTo(0+canvas.width/2+2, 0+canvas.height/2+2);
			// cxt.lineTo(newx+canvas.width/2+2, -newy+canvas.height/2+2);
			// cxt.closePath();
			// cxt.stroke();
		}
	}

});