function ProjectLoaded(oXML){
	document.getElementById('col_right').innerHTML=oXML.responseText;
	document.body.style.cursor='auto';
	window.location.assign("#col_right");
}
function LoadProject(projectID){
	document.body.style.cursor='wait';
	var myConn = new XHConn();
	myConn.connect('ProjectMini.php', 'GET', 'ID='+projectID, ProjectLoaded);
}