/**
 * util.js Some utilities functions
 * 
 * @company Nectil
 * @team Nectil
 * @author Thomas Hermant
 * @version 1.0
 */
 
/**
	 * Return the element specified by a id
	 * @param id element's identifiant
	 * @return a html element
*/
function $id(id){
	return document.getElementById(id);
}

/**
	 * Return a array of element with a specific tag
	 * @param tag elements's tag
	 * @param [from] (optional) specified where we must search elements with specified tag 
	 * @return array of html elements
*/
function $tags(tag,from){
	if(from != undefined){
		el = $id(from);
		if(el != null) return el.getElementsByTagName(tag);
	}	
	return document.getElementsByTagName(tag);
}

/**
	 * Return the style object of a element
	 * @param id element's identifiant
	 * @return a style object
*/
function $style(id){
	var el = $id(id);
	if(el != null) return el.style;
}

/**
	 * Return the value of a input
	 * @param id element's identifiant
	 * @return the input's value
*/
function $value(id){
	var el = $id(id);
	if(el != null) return el.value;
}

// --- very basic animation to avoid Tween js classes to load ---

function appear(id){
	show(id);
	appearid = setTimeout('appear2("'+id+'");',10);
	clearTimeout(disappearid);
}
function appear2(id){
	$style(id).opacity = .66;
	appearid = setTimeout('appear3("'+id+'");',10);
}
function appear3(id){
	$style(id).opacity = 1;
}
function show(id){
	$style(id).display = 'block';
}
function disappear(id){
	$style(id).opacity = .66;
	disappearid = setTimeout('disappear2("'+id+'");',10);
	clearTimeout(appearid);
}
function disappear2(id){
	$style(id).opacity = .33;
	disappearid = setTimeout('hide("'+id+'");',10);
	clearTimeout(appearid);
}
function hide(id){
	$style(id).display = 'none';
}

/**
	 * Return true if the element is display block
	 * @param id element's identifiant
*/
function isDisplayBlock(id){
	return ($style(id).display == 'block' );
}

/**
	 * Hide all element with specified tag and show one element specified by id
	 * @param tag elements's tag to hide
	 * @param id element's identifiant to show
	 * @param [from] (optional) specified where we must search elements with specified tag
	 * @param [name] (optional) to hide tags with a id who content the specific string(name)
*/
function hideAllTagsShowId(tag,id,from,name){
	var elements;
	if(from != undefined){
		elements = $tags(tag,from);
	}else{
		elements = $tags(tag);
	}	
	var n = elements.length;
	for (var i = 0 ; i < n ; i++){
		var current = elements[i];
		var idCurrent = current.id;
		if(name != undefined){
			if(idCurrent.indexOf(name) != -1)
				current.style.display = 'none';
		}else{
			current.style.display = 'none';
		}	
	}
	$style(id).display = 'block';
}

/**
	 * Affect the textDecoration style
	 * @param id element's identifiant
	 * @param type the tpe of the decoration (underline, none, ...)
*/
function setDecoration(id,type){
	$style(id).textDecoration = type;
}

/**
	 * Delete the px of a value and transform this in a number
	 * @param value a string (i.e: 50px)
	 * @return the number of the specified value
*/
function delPx(value){
	if(value == "") return 0;
	return parseFloat(value.substring(0,value.length - 2 ));
}

/**
	 * Add px of a value and transform this in a string
	 * @param value number  (i.e: 50)
	 * @return the string of the specified value with px
*/
function addPx(value){
	if(value == "") return '0px';
	if(typeof(value) == 'string'){
		var pos = value.indexOf('px') 
		if(pos != -1){
			if (pos == value.length - 2){
				return value;
			}else{
				alert('error addPx the value is a string who content px but not at the end')
				return value;
			}
		}	
	}	
	return value + 'px';
}

/**
	 * Change the size of element 
	 * @param value number  (i.e: 50)
	 * @return the string of the specified value with px
*/
function changeHeight(id,size){
	var el = $id(id);
	if(el != null){
		var height = el.offsetHeight;
		var newHeight = height + size;
		el.style.height = addPx(newHeight);
	}	
}

/**
	 * Get the size of the window 
	 * @return the size of th e window
	 * thanks to http://pompage.net
*/
function getWindowHeight() {
	var windowHeight=0;
	if (typeof(window.innerHeight)=='number') {
		windowHeight=window.innerHeight;
	}else{
		if (document.documentElement&&
			document.documentElement.clientHeight) {
			windowHeight = document.documentElement.clientHeight;
		}else{
			if (document.body&&document.body.clientHeight) {
				windowHeight=document.body.clientHeight;
			}
		}
	}
	return windowHeight;
}

/**
	 * Set a footer (relative or absolute) at the right place
	 * thanks to http://pompage.net
*/
function setFooter(content,footer) {
	if (document.getElementById) {
		var windowHeight = getWindowHeight();
		if (windowHeight > 0) {
			var contentHeight = $id(content).offsetHeight;
			var footerElement = $id(footer);
			var footerHeight = footerElement.offsetHeight;
			if (windowHeight-(contentHeight+footerHeight)>=0) {
				$style(footer).position='relative';
				$style(footer).top = addPx(windowHeight - (contentHeight + footerHeight));
			}else {
				$style(footer).position='static';
			}
		}
	}
}

/**
	 * Switch the visibility of a element
	 * @param link is the link who execute the javascript
	 * @param id is the element to switch
*/
function switchVisibility(link,id){
	if(link.className == "open"){
		link.className = "close";
		hide(id);
	}else{
		link.className = "open";
		show(id);
	}
}

/**
	* search all elements with a specific classname
	* @param class_name the class name to search 
	* @return all the elements who have the specified class name 
*/
function getElementsByClass(class_name)
{
	var my_array = document.getElementsByTagName("*");
	var retvalue = new Array();
	var i;
	var j;
	for (i = 0, j = 0; i < my_array.length; i++){
		var c = " " + my_array[i].className + " ";
		if (c.indexOf(" " + class_name + " ") != -1)
			retvalue[j++] = my_array[i];
	}
	return retvalue;
}

/**
	* change all element with a specific class to another class
	* @param seak the class name to search
	* @param to the new class name 
*/
function changeClassName(seak,to){
	var n;
	var finded = getElementsByClass(seak);
	n = finded.length;
	for(var i = 0 ; i < n; i++){
		var el = finded[i];
		el.className = to;
	}	
}

/**
	* hide all elements with a specific classname
	@param class_name the class name
*/
function hideByClass(class_name){
	var elements = getElementsByClass(class_name);
	var n = elements.length;
	for(var i = 0 ; i < n; i++){
		var el = elements[i];
		el.style.display = "none";
	}	
}

/**
	 * resize an element to the size of the "inner" window.
	 * ie : for a flash in safari 
*/
function reSize(id) {
	var style = $style(id);
	if (delPx(style.width) != window.innerWidth || delPx(style.height) != window.innerHeight) {
		style.width = addPx(window.innerWidth);
		style.height = addPx(window.innerHeight);
	}
}


// SPECIFIC TO NECTIL

/**
	 * specific function of Nectil
	 * resize the element with the id "core"
*/
function resizeShell(){
	reSize("core");
}

/**
	 * specific function of Nectil
	 * resize the element with the id "core" only in safari
*/
function resizeSafari(){
	if (navigator.appVersion.indexOf("Safari") != -1) {
		window.onresize = resizeShell;
	}
}

/**
	* specific function of Nectil	 
	* close all 'closable' elements
*/
function closeAll(){
	changeClassName('open','close');
	hideByClass('medias');
	hideByClass('media_section');
	hideByClass('description_section');
}
