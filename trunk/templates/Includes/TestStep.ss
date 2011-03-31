<li class="scenario 			
	<% if IsOutcomePass %>pass<% end_if %>
	<% if IsOutcomeFail %>fail<% end_if %>
	<% if IsOutcomeSkip %>skip<% end_if %>">

	<div class="passfail">
		<label class="pass"><input type="radio" value="pass" name="Outcome[$ID]" <% if IsOutcomePass %>checked="true"<% end_if %> />pass</label>
		<label class="fail fail_$ID"><input type="radio" value="fail" name="Outcome[$ID]" <% if IsOutcomeFail %>checked="true"<% end_if %> />fail</label>
		<label class="skip"><input type="radio" value="skip" name="Outcome[$ID]" <% if IsOutcomeSkip %>checked="true"<% end_if %> />skip</label>
	</div>

	<div class="failseverity">
		<label class="severity1"><input type="radio" value="Severity1" title="major impact, testing cannot continue" name="Severity[$ID]" <% if IsSeverity1 %>checked="true"<% end_if %> /><strong>Critical</strong></label>
		<label class="severity2"><input type="radio" value="Severity2" title="major impact, potentially a workaround existis, testing can continue" name="Severity[$ID]" <% if IsSeverity2 %>checked="true"<% end_if %> /><strong>High</strong></label>
		<label class="severity3"><input type="radio" value="Severity3" title="medium impact, e.g., usability problem" name="Severity[$ID]" <% if IsSeverity3 %>checked="true"<% end_if %> />Medium</label>
		<label class="severity4"><input type="radio" value="Severity4" title="minor or no impact, e.g., cosmetic error" name="Severity[$ID]" <% if IsSeverity4 %>checked="true"<% end_if %> />Low</label>
	</div>

	<div id='scenarioContent_$ID' class="content">
		$StepMarkdown
		
		<% if StepNote %>

		<div class="stepNote" style="display:none;">
			<% if KnownIssues %>
			<div class="knownIssues">
				<h4>Fail Notes:</h4>
				<ul>
				<% control KnownIssues %>
				<li>$Note.XML 
					<% if TestSession.Link %>
					(<a href="$TestSession.Link">see session results</a>)
					<% else %>
					(Session information not available.)
					<% end_if %>
				</li>
				<% end_control %>
				</ul>
			</div>
			<% end_if %>

			<% if PassNotes %>
			<div class="passNotes">
				<h4>Pass Notes:</h4>
				<ul> 
				<% control PassNotes %>
				$ID $ClassName
				<li>$Note.XML 
					<% if TestSession.Link %>
					(<a href="$TestSession.Link">see session results</a>)
					<% else %>
					(Session information not available.)
					<% end_if %>
				</li>
				<% end_control %>
				</ul>
			</div>
			<% end_if %>

			<% if SkipNotes %>
			<div class="skipNotes">
				<h4>Skip Notes:</h4>
				<ul>
				<% control SkipNotes %>
				<li>$Note.XML
					<% if TestSession.Link %>
					(<a href="$TestSession.Link">see session results</a>)
					<% else %>
					(Session information not available.)
					<% end_if %>
				</li>
				<% end_control %>
				</ul>
			</div>
			<% end_if %>
		</div>
		<% end_if %>
	</div>

	<div id="note-{$ID}" class="note">
		<label>Comments:<textarea name="Note[$ID]">$SessionStepResult.Note.Raw</textarea> </label>
		<p class="noteAttachments">
			<label>Attachments</label>
			<input class="ajaxupload" id="ajaxupload-{$ID}" name="Attachment[$ID]" type="file" />
			
			<% control SessionStepResult %>
				<ul class="attachmentList">
					<% if Attachments %>
						<% control Attachments %>
							<li id="file-{$ID}"><a href="$URL" target="new">$Name</a> <a class="removeFile" href="$DeleteLink">Delete</a></li>
						<% end_control %>
					<% end_if %>
				</ul>
			<% end_control %>
		</p>
	</div>

</li>