var product;
var annualFee = false;
var isUser = true;
var alreadyLogged = false;
var packages = 1;

function initShop(classN)
{
	setTagFocus('input');
	setTagFocus('select');	
	setTagFocus('textarea');		
	
	setProduct($value('select-product'));

	if(isset(classN))
	{
		setProduct(classN);
		
		var select = $id('select-product');
		var l = select.options.length;
		for(var i=1;i<l;i++)
		{
			if(select.options[i].value == classN)
			{
				select.selectedIndex = i;
				break;
			}
		}
	}
	
	updateTotal();
}

function initBasket()
{
	displayNumbers();
}

function setVAT(value)
{
	$id('vat').value = value;
}

/* product */
								
function hideProduct()
{
	if(isset(product))
	{
		var el = getProductEl();
		el.className += " invisible";
	}
}

function showProduct()
{
	if(isset(product))
	{
		var myProductEl = getProductEl();
		myProductEl.className = getWithout(myProductEl.className,['invisible']);
	}
}

function getProductEl()
{
	return $class(product)[0];
}

function setProduct(classname)
{
	if(! isempty(classname))
	{
		if($id('options') != null)
			show('options');	
			
		hideProduct();
	
		product = classname;
	
		showProduct();
		updateTotal();
	}
	else
	{
		if($id('options') != null)
			hide('options');
			
		delete product;
	}
}

/* laps */

function setLaps(value)
{
	setAnnualFee(parseFloat(value) >= 12);
	
	/*
	var el = $tagsClasses("p",['annual-fee'],getProductEl())[0];

	if(value >= parseFloat(12))
	{
		el.className = getWithout(el.className,['invisible']);
		updateTotal()
	}
	else
	{
		if(! isIn(el.className,['invisible']))
			el.className += " invisible";
		setAnnualFee(false);
	}
	*/
}

function getLaps()
{
	return $tagsClasses("select",['laps'],getProductEl())[0].value;
}

/* auto-renew*/

function switchCheckbox(className)
{
	var checkbox = $tagsClasses("input",[className],getProductEl())[0];
	logs('checkbox : ' + checkbox);
	logs('before : ' + checkbox.checked);	
	checkbox.checked = 	! checkbox.checked;
	logs('after : ' + checkbox.checked);		
}

/*package*/

function setPackage(value)
{
	packages = value;
	updateTotal();
}

/* gigas */

function setGigas(value)
{
	updateTotal();
}

function getGigas()
{
	return $tagsClasses("select",['storage'],getProductEl())[0].value;
}

function setAnnualFee(b)
{
	annualFee = b;
	
	var paymentModeID = $tagsClasses("input",['paymentModeID'],getProductEl());
	if(b)
		paymentModeID.value = $tagsClasses("input",['yearlyID'],getProductEl());
	else	
		paymentModeID.value = $tagsClasses("input",['monthlyID'],getProductEl());
	
	setTotal(computeTotal());
}

/* price */

function getGigaPrice()
{
	if(annualFee)
	{
		if(isUser)
		{
			return	$tagsClasses('input',['yUserGigaPrice'],getProductEl())[0].value;
		}
		else
		{
			return	$tagsClasses('input',['yAgencyGigaPrice'],getProductEl())[0].value;
		}
	}
	else
	{
		if(isUser)
		{
			return	$tagsClasses('input',['mUserGigaPrice'],getProductEl())[0].value;
		}
		else
		{
			return	$tagsClasses('input',['mAgencyGigaPrice'],getProductEl())[0].value;
		}
	}
}

function getPrice()
{
	if(annualFee)
	{
		if(isUser)
		{
			return	$tagsClasses('input',['yUserPrice'],getProductEl())[0].value;
		}
		else
		{
			return	$tagsClasses('input',['yAgencyPrice'],getProductEl())[0].value;
		}
	}
	else
	{
		if(isUser)
		{
			return	$tagsClasses('input',['mUserPrice'],getProductEl())[0].value;
		}
		else
		{
			return	$tagsClasses('input',['mAgencyPrice'],getProductEl())[0].value;
		}
	}
}

function setInputPrice(value,isGiga)
{
	isGiga = initValue(isGiga,false);
	
	if(isGiga)
	{
		$tagsClasses('input',['gigaPrice'],getProductEl())[0].value = value;
	}
	else
	{
		$tagsClasses('input',['price'],getProductEl())[0].value = value;
	}
}

/* total */

function computeTotal()
{
//	logs("computeTotal");

	if(product != "officity-support")
	{
		var price = getPrice();	
		var gigaPrice = getGigaPrice();
		
		setInputPrice(price);
		setInputPrice(gigaPrice,true);
		
		var laps = getLaps();
		var gigas = getGigas();
		
		return (laps * price) + ((gigas-1) * gigaPrice);
	}
	else
	{
		var totalPrice,hours,interventions;
		var nbDefaultHours = parseFloat($value('nbHours'));
		var nbDefaultInterventions = parseFloat($value('nbInterventions'));
		var hourPrice = parseFloat($value('hourPrice'));				
		var interventions = parseFloat($value('interventions'));		
		var specialHourPrice = parseFloat($value('specialHourPrice'));		
		var specialInterventions = parseFloat($value('specialInterventions'));						
		
		if(packages == 0)
		{
			// visibility
			hide('hours');
			var el = $id('package-manual');
			el.className = '';
			
			// hours
			var elHours = $tagsClasses('input',['hours'],el)[0];
			hours = Math.ceil(parseFloat(elHours.value));
			
			if(isNaN(hours))
			{
				hours = 0;
				elHours.value = '';
			}
			else
			{
				elHours.value = hours;				
			}
			
			// interventions && totalPrice
			if(hours < nbDefaultHours)
			{
				interventions = Math.ceil(hours * interventions);
				totalPrice = hours * hourPrice;							
			}
			else
			{
				interventions = Math.ceil(hours * specialInterventions);
				totalPrice = hours * specialHourPrice;			
			}
		}
		else
		{
			// visibility
			show('hours');
			$id('package-manual').className = 'invisible';
			
			// hours && interventions && totalPrice
			hours = parseFloat(packages) * nbDefaultHours;
			totalPrice = hours * specialHourPrice;
			interventions = Math.ceil(hours * specialInterventions);
		}
		
		$tagsClasses('input',['hours'],$id('hours'))[0].value = hours;
		$tagsClasses('input',['intervations'],$id('intervations'))[0].value = interventions;
		
		return totalPrice;
	}
}

function setTotal(value)
{
	$tagsClasses("input",['total'],getProductEl())[0].value = value;
	displayNumbers();
}

function updateTotal()
{
	setTotal(computeTotal());
}

/* submit */

function submitOrder()
{
	if(isset(product))
	{
		logs("submit form " + product);
		$id(product).submit();
	}
}