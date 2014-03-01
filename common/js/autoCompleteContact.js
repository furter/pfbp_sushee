function autoCompleteContact(cID) {
	$.get("http://www.prixfernandbaudinprijs.be/common/scripts/SearchContacts_JSON.php", { cID:cID },
	  function(data){
		var reponse = eval('(' + data + ')'); 
		contacts = reponse.reponse;
		$("#DENOMINATION").focus().autocomplete(contacts);
	});
}