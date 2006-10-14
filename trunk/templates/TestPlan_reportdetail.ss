<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<% base_tag %>
<title>Perform a Test</title>
<link rel="stylesheet" type="text/css" href="regress/css/TestPlan.css" />
</head>
<body>
<h1>Peform a test</h1>

<% control TestPlan %>
<h1>Test Results for '$Title'</h1>
<% end_control %>

<% control TestSession %>
<p>Tested on: $Created.Nice</p>

<p>Passes: $NumPasses<br />
Failures: $NumFailures</p>

<% if Failures %>
	<h2>Failures</h2>
	<ul>
	<% control Failures %>
	<li><b>$TestStep.Step.XML</b><br />
	$FailReason</li>
	<% end_control %>
	</ul>
<% end_if %>


<% end_control %>

</body>
</html>