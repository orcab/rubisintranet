function dumper (u_var, n_depth) {

if (n_depth == null)
n_depth = 0;

var s_tabs = "\n";
for (var i = 0; i < n_depth; i++)
s_tabs += "\t";

n_depth++;
// verify type
if (typeof(u_var) == 'number')
return u_var;
else if (typeof(u_var) == 'string')
return "'" + u_var + "'";
else if (typeof(u_var) == 'boolean')
return (u_var ? 'true' : 'false');
else if (typeof(u_var) == 'undefined' || u_var == null)
return 'null';
else if (typeof(u_var) == 'function')
return 'function () {alert(\'function was not saved\')}';

// detect numeric or symbolic keys
var b_hash = false;
for (var i in u_var)
if (isNaN(Number(i))) {
b_hash = true;
break;
}

var s_output = '',
b_first = true;
if (b_hash) {
s_output += "{";
for (var i in u_var) {
s_output += (b_first ? '' : ",") + s_tabs + "\t'" + i + "': " + dumper(u_var, n_depth);
b_first = false;
}
s_output += s_tabs + "}";
}
else {
s_output += "[";
for (var i = 0; i < u_var.length; i++) {
s_output += (b_first ? '' : ",") + s_tabs + "\t" + dumper(u_var, n_depth);
b_first = false;
}
s_output += s_tabs + "]";
}
return s_output;
}