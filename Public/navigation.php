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
	<GET refresh="monthly"><MONTHS/></GET>
	<GET refresh="monthly"><DAYS/></GET>
	<GET refresh="monthly"><COUNTRIES/></GET>
	<GET refresh="monthly"><ENUM><START>0</START><END>100</END></ENUM></GET>
	<GET refresh="monthly" name="years"><CATEGORIES path="/contact/contact_details/prix"/><RETURN depth="all"/></GET>
	<GET refresh="monthly" name="book_categories"><CATEGORIES path="/media/livre"/><RETURN depth="all"/></GET>
	<GET refresh="monthly" name="media_dependencies"><DEPENDENCYENTITY from="media" /></GET>
	<GET name="website">
		<MEDIA ID="3500"/>
		<RETURN depth="3">
			<INFO><MEDIATYPE/></INFO>
			<DESCRIPTION/>
			<DEPENDENCIES>
				<DEPENDENCY type="Tools">
					<INFO>
						<MEDIATYPE/>
						<PAGETOCALL/>
					</INFO>
					<DESCRIPTIONS>
						<TITLE/>
						<SUMMARY/>
					</DESCRIPTIONS>					
				</DEPENDENCY>
				<DEPENDENCY type="mediaNavigation">
					<INFO>
						<MEDIATYPE/>
						<PAGETOCALL/>
					</INFO>
					<DESCRIPTIONS>
						<TITLE/>
						<SUMMARY/>
					</DESCRIPTIONS>
					<DEPENDENCIES>
						<DEPENDENCY type="mediaNavigation">
							<INFO>
								<MEDIATYPE/>
								<PAGETOCALL/>
							</INFO>
							<DESCRIPTIONS>
								<TITLE/>
								<SUMMARY/>
							</DESCRIPTIONS>
						</DEPENDENCY>
					</DEPENDENCIES>
				</DEPENDENCY>
			</DEPENDENCIES>
		</RETURN>
	</GET>
	<DISPLAY static="true" module="MEDIA" mediatype="Book" mode="website_preview" tag="ul" childtag="li">
		<DATA service="DESCRIPTION" path="TITLE"/>
		<GROUP class="PFBproperties" tag="ul" childtag="li">
			<DATA service="CATEGORY" path="concours"/>
			<DATA service="DESCRIPTION" path="YEAR"/>
		</GROUP>
		<DATA service="DEPENDENCY" type="BookToGraphist"/>
		<DATA service="DEPENDENCY" type="BookToEditor"/>
		<DATA service="DEPENDENCY" type="BookToPrinter"/>
			<DATA service="CATEGORY" path="book_theme"/>
		<LINK type="arrow"/>
	</DISPLAY>
</QUERY>';
?>