// overload String Object

String.prototype.ltrim = function() {
	//Match spaces at beginning of text and replace with a null string
	return this.replace(/^\s+/,'');
}
    
String.prototype.rtrim = function() {
	//Match spaces at end of text and replace with a null string
	return this.replace(/\s+$/,'');
}

String.prototype.trim = function() {
	return this.ltrim().rtrim();
}