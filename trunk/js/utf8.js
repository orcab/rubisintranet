// overload String Object

String.prototype.utf8_decode = function() {
	var tmp_arr = [], i = 0, ac = 0, c1 = 0, c2 = 0,   c3 = 0; 
	var str_data = this;
	str_data += '';

	while (i < str_data.length) {        c1 = str_data.charCodeAt(i);
		if (c1 < 128) {
			tmp_arr[ac++] = String.fromCharCode(c1);
			i++;
		} else if (c1 > 191 && c1 < 224) {            c2 = str_data.charCodeAt(i + 1);
			tmp_arr[ac++] = String.fromCharCode(((c1 & 31) << 6) | (c2 & 63));
			i += 2;
		} else {
			c2 = str_data.charCodeAt(i + 1);            c3 = str_data.charCodeAt(i + 2);
			tmp_arr[ac++] = String.fromCharCode(((c1 & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
			i += 3;
		}
	} 
	return tmp_arr.join('');
}



String.prototype.utf8_encode = function() {
	var string = this;
	string = string.replace(/\r\n/g,"\n");
	var utftext = "";

	for (var n = 0; n < string.length; n++) {
		var c = string.charCodeAt(n);

		if (c < 128) {
			utftext += String.fromCharCode(c);
		}
		else if((c > 127) && (c < 2048)) {
			utftext += String.fromCharCode((c >> 6) | 192);
			utftext += String.fromCharCode((c & 63) | 128);
		}
		else {
			utftext += String.fromCharCode((c >> 12) | 224);
			utftext += String.fromCharCode(((c >> 6) & 63) | 128);
			utftext += String.fromCharCode((c & 63) | 128);
		}
	}
	return utftext;
}