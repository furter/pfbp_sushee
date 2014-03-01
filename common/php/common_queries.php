<?php

function get_media($mID, $name='media', $depth='2', $refresh='daily') {
	$command = '<GET name="'.$name.'" refresh="'.$refresh.'">
		<MEDIA ID="'.$mID.'"/>
		<RETURN depth="'.$depth.'">
			<INFO><MEDIATYPE/><PAGETOCALL/></INFO>
			<DESCRIPTIONS/>
			<DEPENDENCIES>
				<DEPENDENCY type="mediaContent">
					<INFO><MEDIATYPE/><PAGETOCALL/><TEMPLATE/></INFO>
					<DESCRIPTION/>
				</DEPENDENCY>
			</DEPENDENCIES>
		</RETURN>
	</GET>';
	return $command;
}

?>