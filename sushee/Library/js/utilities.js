/**
 * utilities.js Some utilities functions
 * 
 * @company Nectil
 * @team Nectil
 * @author Thomas Hermant
 * @version 1.2
 * @date 2008-06-13
 */
 
/**
	 * log a message in the firedebug console
	 * @param e message to log in the firedebug console
	 * @param b message to log in the "Logger"div
	 * @param type : 'ERROR', 'LOG', 'WARNING'	 
*/ 
function logs(e,b,type)
{
	b = initValue(b,false);
	type = initValue(type,'LOG');
	
	/*switch(type)
	{
		case 'ERROR':
			console.error(e);
		break;
		
		case 'LOG':
			console.log(e);
		break;
		
		case 'WARNING':
			console.warn(e);
		break;
	}*/
	
	if(b == true)
	{
		if($id('Logger') != null)
		{
			show('Logger'); 
			$id('Logger').innerHTML = $inner('Logger') + '<br/>'+timeString(new Date())+' - '+ e;
		}
	}
}

/**
	 * test if a variable is setted
	 * @param variable
	 * @return boolean who tell if the varibale is different of undefined or not
*/ 
function isset(variable)
{
	return (variable != undefined);
}

/**
	 * test if a variable is setted (undefined or not)
	 * @param variable
	 * @return boolean
*/ 
function isempty(variable)
{
	return (variable == '');
}

/**
	 * return a intialization value for  a viriable
	 * @param variable to test if defined 
	 * @param value default value for this variable
*/
function initValue(variable,defaultValue)
{
	return (variable == undefined) ? defaultValue : variable;
}
 
/**
	 * Return the element specified by a id
	 * @param id element's identifiant
	 * @return a html element
*/
function $id(id)
{
	return document.getElementById(id);
}

/**
	 * Return the innerHTML of the element specified id
	 * @param id element's identifiant
	 * @return innerHTML (string)
*/
function $inner(id)
{
	var el = $id(id); 
	if(el != null)
	{
		if(el.innerHTML != undefined)
		{
			return el.innerHTML;
		}	
		else
		{
			logs("$inner("+id+") : the element don't have the property innerHTML",false,'WARNING');
		}
	}
	else
	{
		logs("$inner : " + id + " : doesn't exist",false,'WARNING');
	}
}

/**
	 * Return a array of element with a specific tag
	 * @param tag elements's tag
	 * @param [from] (optional) specified where we must search elements with specified tag : id (string) or element himself 
	 * @return array of html elements
*/
function $tags(tag,from)
{
	if(from != undefined)
	{
		el = (from.id != undefined) ? from : $id(from);
		if(el != null)
		{
			return el.getElementsByTagName(tag);
		}
	}	
	return document.getElementsByTagName(tag);
}

/**
	 * Return a array of element with a specific tag and classes
	 * @param tag elements's tag
	 * @param classes matching classes
	 * @param [from] (optional) specified where we must search elements with specified tag 
	 * @return array of html elements
*/
function $tagsClasses(tag,classes,el)
{
	var list = [];
	var tags = $tags(tag,el);

	var l = tags.length;
	for(var i = l;--i >= 0;)
	{
		var t = tags[i];
		if(isAllIn(t.className,classes))
		{
			list.push(t);
		}
	}
	return list;
}

/**
	 * Return the style object of a element
	 * @param id element's identifiant
	 * @return a style object
*/
function $style(id)
{
	var el = $id(id);
	if(el != null) 
	{
		return el.style;
	}	
	else
	{
		logs("$style : " + id + " : doesn't exist",false,'WARNING');
	}
}

/**
	 * Return the value of a input
	 * @param id element's identifiant
	 * @return the input's value
*/
function $value(id)
{
	var el = $id(id);
	if(el != null)
	{
		return el.value;
	}
	else
	{
		logs("$value : " + id + " : doesn't exist",false,'WARNING');
	}
}

/**
	 * Show the element with specified id
	 * @param id element's identifiant to show
*/
function show(id)
{
	if($id(id) != null)
	{
		$style(id).display = 'block';
	}
	else
	{
		logs("show : " + id + " : doesn't exist",false,'WARNING');
	}	
}

/**
	 * Hide the element with specified id
	 * @param id element's identifiant to hide
*/
function hide(id)
{
	if($id(id) != null)
	{
		$style(id).display = 'none';
	}
	else
	{
		logs("hide : " + id  + " : doesn't exist",false,'WARNING');
	}
}

function hideElements(elements/*array*/)
{
	for (var i=0; i < elements.length; i++) {
		elements[i].style.display = "none";
	};
}

function showElements(elements/*array*/)
{
	for (var i=0; i < elements.length; i++) {
		elements[i].style.display = "";
	};
}


/**
	 * Return true if the element is display block
	 * @param id element's identifiant
*/
function isDisplayBlock(id)
{
	if($id(id) != null)
	{
		return ($style(id).display == 'block' );
	}
	else
	{
		logs("isDisplayBlock : " + id + " : doesn't exist",false,'WARNING');
	}
}

/**
	 * Hide all element with specified tag and show one element specified by id
	 * @param tag elements's tag to hide
	 * @param id element's identifiant to show
	 * @param [from] (optional) specified where we must search elements with specified tag
	 * @param [name] (optional) to hide tags with a id who content the specific string(name)
*/
function hideAllTagsShowId(tag,id,from,name)
{
	var elements;
	if(from != undefined)
	{
		elements = $tags(tag,from);
	}
	else
	{
		elements = $tags(tag);
	}
		
	var n = elements.length;
	for (var i = 0 ; i < n ; i++)
	{
		var current = elements[i];
		var idCurrent = current.id;
		if(name != undefined)
		{
			if(idCurrent.indexOf(name) != -1)
				current.style.display = 'none';
		}
		else
		{
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
function setDecoration(id,type)
{
	$style(id).textDecoration = type;
}

/**
	 * Delete the px of a value and transform this in a number
	 * @param value a string (i.e: 50px)
	 * @return the number of the specified value
*/
function delPx(value)
{
	if(value == "") 
		return 0;
	return parseFloat(value.substring(0,value.length - 2 ));
}

/**
	 * Add px of a value and transform this in a string
	 * @param value number  (i.e: 50)
	 * @return the string of the specified value with px
*/
function addPx(value)
{
	if(value == "") return '0px';
	if(typeof(value) == 'string')
	{
		var pos = value.indexOf('px') 
		if(pos != -1)
		{
			if (pos == value.length - 2)
			{
				return value;
			}
			else
			{
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
function changeHeight(id,size)
{
	var el = $id(id);
	if(el != null)
	{
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
function getWindowHeight() 
{
	var windowHeight=0;
	if (typeof(window.innerHeight)=='number') 
	{
		windowHeight=window.innerHeight;
	}
	else
	{
		if (document.documentElement&&
			document.documentElement.clientHeight) 
		{
			windowHeight = document.documentElement.clientHeight;
		}
		else
		{
			if (document.body&&document.body.clientHeight) 
			{
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
function setFooter(content,footer) 
{
	if (document.getElementById) 
	{
		var windowHeight = getWindowHeight();
		if (windowHeight > 0) 
		{
			var contentHeight = $id(content).offsetHeight;
			var footerElement = $id(footer);
			var footerHeight = footerElement.offsetHeight;
			if (windowHeight-(contentHeight+footerHeight)>=0) 
			{
				$style(footer).position='relative';
				$style(footer).top = addPx(windowHeight - (contentHeight + footerHeight));
			}
			else
			{
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
function switchVisibility(link,id)
{
	if(link.className == "open")
	{
		link.className = "close";
		hide(id);
	}
	else
	{
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
	var els = document.getElementsByTagName("*");
	var result = [];

	for (var i = els.length; --i >= 0;)
	{
		var el = els[i];
		var c = " " + el.className + " ";
		if (c.indexOf(" " + class_name + " ") != -1)
			result.push(el);
	}
	
	return result;
}

function $class(class_name)
{
	return getElementsByClass(class_name);
}

/**
	* change all element with a specific class to another class
	* @param seak the class name to search
	* @param to the new class name 
*/
function changeClassName(seak,to)
{
	var n;
	var finded = $class(seak);
	n = finded.length;
	for(var i = 0 ; i < n; i++)
	{
		var el = finded[i];
		el.className = to;
	}	
}

/**
	* hide all elements with a specific classname
	@param class_name the class name
*/
function hideByClass(class_name)
{
	var elements = $class(class_name);
	var n = elements.length;
	for(var i = 0 ; i < n; i++)
	{
		var el = elements[i];
		el.style.display = "none";
	}	
}

/**
	 * resize an element to the size of the "inner" window.
	 * ie : for a flash in safari 
*/
function reSize(id) 
{
	var style = $style(id);

	if (delPx(style.width) != delPx(window.innerWidth) || delPx(style.height) != delPx(window.innerHeight)) 
	{
		style.width = addPx(window.innerWidth);
		style.height = addPx(window.innerHeight);
	}
}

/**
	* Format all element with the number 'class' 
	* -> ie : 3.5 -> 3.50
	* -> ie : 3 -> 3.00
*/
function displayNumbers()
{
	var numbers = $class('number');
	var l = numbers.length;
	for(var i=0;i<l;i++)
	{
		var n = numbers[i];
		if(n == '[object HTMLInputElement]')
			n.value = getDisplayNumber(n.value);
		else
			n.innerHTML = getDisplayNumber(n.innerHTML);
	}
}

/**
	* Format number in displayable number
	* -> ie : 3.5 -> 3.50
	* -> ie : 3 -> 3.00
	* -> ie : 3.05 -> 3.05 
	@param value
	@retrun the formated value
*/
function getDisplayNumber(value)
{
	value = parseFloat(value);
	
	var integer = Math.floor(value);
	var decimal = Math.round((value - integer)*100);
	
	if(decimal == 0) 
		return integer + '.' + '00';
	else if(decimal < 10)	
		return integer + '.0' + decimal;
	else
		return integer + '.' + decimal;
}

/**
	* get all 'tag' elements from the el 
	@param tag the spcified tag to search
	@param el the element where the search is operate
	
function getTagFromEl(tag,el)
{
	return el.getElementsByTagName(tag);
}
*/

/**
	* Add to all 'tag' element 'onfocus' and 'onblur' properties
	@param tag the spcified tag to search
*/
function setTagFocus(tag)
{
	var els = document.getElementsByTagName(tag);
	var l = els.length;
	for(var i = 0 ; i<l ; i++)
	{
		var el = els[i];
		if(! isIn(el.className,'readonly'))
		{
			el.setAttribute('onfocus','setFocus(this);');
			el.setAttribute('onblur','setBlur(this);');		
		}
	}
}

/**
	* Add to an element the 'focus' class
	@param el the element
*/
function setFocus(el)
{
	el.className += " focus" ;
}

/**
	* remove from an element the 'focus' class
	@param el the element
*/
function setBlur(el)
{
	el.className = getWithout(el.className,['focus']) ;
}

/**
	* remove from a string the first occurence of all words in the 'wordsout array'
	@param string : the string to parse
	@param wordsToOut : array that contains all words to get out from the string
	@separator [optional] : the word's separator by default ' ' (blank space)
*/
function getWithout(string,wordsToOut,separator)
{
	separator =  initValue(separator,' ');
	
	var words = string.split(separator);
	var l = words.length;
	var myFinalWords = [];
	for(var i = 0; i< l;i++)
	{
		var word = words[i];
		m = wordsToOut.length;
		var isIn = false;
		for (var j = 0; j < m; j++) 
		{
			if(wordsToOut[j] == word)
			{
				wordsToOut.splice(j,1);
				isIn = true;
				break;
			}
		}
		if(!isIn)
			myFinalWords.push(word);
	}
	return myFinalWords.join(" ");
}

/**
	* test the presence of a word in a string
	@param string : the string to parse
	@param word : the word to test
	@separator [optional] : the word's separator by default ' ' (blank space)
*/
function isIn(string,word,separator)
{
	separator = initValue(separator,' ');
	
	if((string.indexOf(word) == -1))
	{
		return false;
	}
	else
	{
		var stringSplitted = string.split(separator);
		var l = stringSplitted.length;
		for(var i=0;i<l;i++)
		{
			if(stringSplitted[i] == word)			
			{
				return true;
			}
		}
		return false;
	}
}

/**
	* test the presence of words in a string
	@param string : the string to parse
	@param word : array of word to test
*/
function isAllIn(string,words)
{
	var b = true;
	var l = words.length;
	for(var i = 0 ; i < l ; i++)
	{
		b = isIn(string,words[i]);
		if(!b)
			return b;
	}
	return b;
}

/**
	* test if a year is Bisextil
	@param y the year to test
	@return boolean
*/
function isBisextil (y)
{
	return (y%400 != 0 && y%4 == 0);
}

/**
	* get the number of days of the month from a specific month and year
	@param m the month
	@param y the year
	@return nmuber of days
*/
function getNumberDays(m,y)
{
	switch(m)
	{
		case 1:
		case 3:
		case 5:
		case 7:
		case 8:
		case 10:
		case 12:
			return 31;
		break;
		
		case 2: 
			if(isBisextil(y))
				return 29;
			else	
				return 28;	
		break;
		
		default:
			return 30;
	}
}

/**
	 * format a number of a date to sql format
	 * @param the value to format
*/
function toSQL(value)
{
	return (value < 10) ? '0' + value : value;
};

/**
	 * Get a number from a sql date number
	 * @param the value to unformat
*/
function unSQL(value)
{
	return parseInt(value)
};

/**
	 * set a cookie
	 * @param name name of the cookie
	 * @param value value of the cookie
	 * @param expire expiration of the cookie
	 * @param path cookie path
	 * @param domain cookie domain 
	 * @param secure the scurity of the cookie
*/
function setCookie(name,value,expire,path,domain,secure)
{
	var cookie = name + "=" + escape(value);
	cookie += isset(expire) ? "; expires=" + expires.toGMTString() : "";
	cookie += isset(path) ? "; path=" + path : "";
	cookie += isset(domain) ? "; domain=" + domain : "";
	cookie += isset(secure) ? "; secure" : "";

	document.cookie = cookie;
}

/**
	 * get the value of a cookie
	 * @param name name of the cookie
*/
function getCookieValue(name)
{
	var arg = name + "=";
	var argLenght = arg.length;
	var cookiesLength = document.cookie.length;
	var i = 0;
	
	while (i < cookiesLength)
	{
		var j = i + argLenght;
		if (document.cookie.substring(i,j) == arg) 
		{
			var to = document.cookie.indexOf (";", j);
			if (to == -1) 
				to = document.cookie.length;
				
			return unescape(document.cookie.substring(j,to));
		}
			
		i = document.cookie.indexOf(" ",i) + 1;
		
		if (i==0) 
			break;
	}
	
	return null;
}

/**
	 * Delete all iteration of values from a string
	 * @param string string to clear
	 * @param array all values to delete from the string
*/
function clearString(string,array)
{
	for (var i = 0; i < array.length ; i++)
	{
		var splitted = string.split(array[i]);	
		string = splitted.join('');	
	}
	return string;
}

/**
	 * test is the string is castable to a number
	 * @param value string to test
	 * @return boolean
*/
function isNum(value)
{
	return (! isNaN(parseFloat(value)));
}

/**
	 * test is the value is alpha-numeric
	 * @param value string to test
	 * @return boolean
*/
function isAlphaNum(value)
{
	var valid = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	
	if (valid.indexOf(value) == -1 || !isNum(c)) return false;
	else return true;
}

/**
	 * test is the value is alphabetic
	 * @param value string to test
	 * @return boolean
*/
function isAlpha(c)
{
	var valid = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	if (valid.indexOf(c) == -1) return false;
	else return true;
}

/**
	 * test is the string is all numeric
	 * @param string string to test
	 * @return boolean
*/
function allNum(string)
{
	for(var i = 0 ; i < string.length ; i++)
	{
		if(!isNum(string.charAt(i)))
		{
			return false;
		}
	}
	return true;
}

/**
	 * test is the string is all alphabetic
	 * @param string string to test
	 * @return boolean
*/
function allAlpha(string)
{
	for(var i = 0 ; i < string.length ; i++)
	{
		if(!isAlpha(string.charAt(i)))
		{
			return false;
		}
	}
	return true;
}

/**
	 * test is the string passed the validationFunction execpt for exceptions indexes
	 * @param string string to test
	 * @param validationFunction function who validate the test
	 * @param exceptions array of index who don't must pass the test
	 * @return boolean
*/
function allExcept(string,validationFunction,exceptions)
{
	for(var i = 0 ; i < string.length ; i++)
	{
		if(typeof(exceptions[i]) != "function")
		{
			if(! validationFunction(string.charAt(i)))
			{
				return false;
			}
		}
		else
		{
			if(!exceptions[i](string.charAt(i)))
			{
				return false;
			}
		}
	}
	return true;
}

/**
	 * specific function of Nectil
	 * resize the element with the id "core"
*/
function resizeShell()
{
	reSize("core");
}

/**
	 * specific function of Nectil
	 * resize the element with the id "core" only in safari
*/
function resizeSafari()
{
	if (navigator.appVersion.indexOf("Safari") != -1) 
	{
		window.onresize = resizeShell;
	}
}

/**
	* specific function of Nectil	 
	* close all 'closable' elements
*/
function closeAll()
{
	changeClassName('open','close');
	hideByClass('medias');
	hideByClass('media_section');
	hideByClass('description_section');
}
