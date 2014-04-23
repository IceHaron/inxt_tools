$(document).ready(function() {
	var canvas=document.getElementById("map");
	var cxt=canvas.getContext("2d");
	var padding = 30;
	var map = JSON.parse($('#strForMap').text());
	var dots = map.dots;
	var jumps = map.jumps;
	var maxx = minx = parseFloat(dots[0].pos_x);
	var maxy = miny = parseFloat(dots[0].pos_y);
	// var maxz = minz = parseFloat(map[0].pos_z);
	var coords = {};
	for (i in dots) {
		var dot = dots[i];
		coords[dot.name] = {};
		if (parseFloat(dot.pos_x) < minx) minx = parseFloat(dot.pos_x);
		if (parseFloat(dot.pos_x) > maxx) maxx = parseFloat(dot.pos_x);
		if (parseFloat(dot.pos_y) < miny) miny = parseFloat(dot.pos_y);
		if (parseFloat(dot.pos_y) > maxy) maxy = parseFloat(dot.pos_y);
		// if (parseFloat(dot.pos_z) < minz) minz = parseFloat(dot.pos_z);
		// if (parseFloat(dot.pos_z) > maxz) maxz = parseFloat(dot.pos_z);
	}
	var scaleX = (canvas.width-padding*2) / (maxx - minx);
	var scaleY = (canvas.height-padding*2) / (maxy - miny);
	// scaleZ = 1 / (Math.abs(maxz + minz));
	cxt.fillStyle = "black";
	cxt.fillRect(0, 0, canvas.width, canvas.height);
	cxt.fillStyle = 'white';
	cxt.strokeStyle = 'grey';
	cxt.font = "normal 8pt Sans-Serif";
	for (i in dots) {
		var dot = dots[i];
		var x = (parseFloat(dot.pos_x) + Math.abs(minx)) * scaleX + padding - 25;
		var y = (parseFloat(dot.pos_y) + Math.abs(miny)) * scaleY + padding;
		coords[dot.name]["x"] = x;
		coords[dot.name]["y"] = y;
	}
	for (i in jumps) {
		var jump = jumps[i];
		cxt.beginPath();
		cxt.moveTo(coords[jump.fromName]["x"]+2, coords[jump.fromName]["y"]+2);
		cxt.lineTo(coords[jump.toName]["x"]+2, coords[jump.toName]["y"]+2);
		cxt.closePath();
		cxt.stroke();
	}
	for (i in dots) {
		var dot = dots[i];
		cxt.fillRect(coords[dot.name]["x"], coords[dot.name]["y"], 4, 4);
		cxt.fillText(dot.name,coords[dot.name]["x"], coords[dot.name]["y"]-1);
	}
});