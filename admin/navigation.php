<?php
return '
<QUERY>
	<!-- languages menu -->
	<SEARCH name="published_languages" refresh="daily">
		<LANGUAGES profile="Media"/>
	</SEARCH>
	<!-- microlabels -->
	<SEARCH name="labels" refresh="weekly">
		<LABELS/>
	</SEARCH>
	<GET name="numbers" refresh="monthly">
		<ENUM>
			<START>0</START>
			<END>100</END>
		</ENUM>
	</GET>
	<GET refresh="monthly"><MONTHS/></GET>
	<GET refresh="monthly"><DAYS/></GET>
	<GET refresh="monthly"><COUNTRIES/></GET>
	<GET name="website" refresh="monthly">
		<MEDIA ID="3495"/>
		<RETURN>
			<INFO><MEDIATYPE/></INFO>
			<DESCRIPTION/>
			<DEPENDENCIES>
				<DEPENDENCY type="mediaNavigation">
					<INFO>
						<MEDIATYPE/>
						<PAGETOCALL/>
					</INFO>
					<DESCRIPTIONS>
						<TITLE/>
						<HEADER/>
						<BODY />
						<URL />
					</DESCRIPTIONS>
				</DEPENDENCY>
			</DEPENDENCIES>
		</RETURN>
	</GET>
	<GET refresh="monthly"><LIST domain="admin"/></GET>
</QUERY>';
?>