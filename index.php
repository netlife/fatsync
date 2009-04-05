<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>FatSync</title>
		<script type="text/javascript">//<![CDATA[

		function load(file) {
			var iframe=document.getElementById('syncframe');
			iframe.src=file;
		}

		//]]></script>
    </head>
    <body>
        <p>
			<input type="button" value="Sync" onclick="load('sync.php')" />
			<input type="button" value="View local data" onclick="load('view-data.php')" />
			<input type="button" value="Delete local data" onclick="load('delete-data.php')" />
		</p>
		<iframe style="width:100%;height:95%" id="syncframe"/>
    </body>
</html>
