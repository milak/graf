<?php
    require("dao/daoutil.php");
?>
<html>
<head>
	<title>GRAF - setup</title>
 	<link rel="stylesheet" href="css/graf.css">
 	<link rel="stylesheet" href="lib/pure/pure-min.css">
 	<link rel="stylesheet" href="lib/jquery/ui/1.12.1/themes/base/jquery-ui.css">
 	<script type="text/javascript" src="lib/jquery/jquery-3.2.1.min.js"></script>
	<script type="text/javascript" src="lib/jquery/ui/1.12.1/jquery-ui.js"></script>
 	<script type="text/javascript">
 		function onDAOChange(){
 	 		var frameDAOSetup = $("#frameDAOSetup");
 	 		var value = $("#selectDAO").val();
 	 		if (value == "null"){
 	 			frameDAOSetup.attr("src","about:blank");
 	 		} else {
 	 			frameDAOSetup.attr("src","dao/"+value+"/setup.php");
 	 		}
 		}
 	</script>
</head>
<body>
<div style="width:10%;height:100%;background-color:#5588EE; float: left;">
	<div style="width:100%;height:100px;text-align:center;color:white;vertical-align:middle;padding-top:10px">
		<div style="font-size:35px">GRAF</div>
		<div style="font-size:14px">Graphic Rendering<br/>Architect Framework</div>
	</div>
</div>
<div style="width:90%;height:100%;float:right">
	<table style="width: 100%; height: 100%">
	<tr style="height: 60px">
	<td rowspan="3"></td>
	</tr>
	<tr>
		<td style="width: 20%"></td>
		<td style="border-style: outset;">
            <div style="width: 100%;text-align: center;background-color: lightblue">GRAF Setup</div>
            <form action="setup.php" method="post" class="pure-form pure-form-aligned">
            <fieldset>
            		<div class="pure-control-group">
            			<label for="selectDAO">Sélectionnez le type de données</label>
                        <select id="selectDAO" onchange="onDAOChange()">
                        	<option value="null">~~choisissez un type de données~~</option>
                        	<option value="itop">itop</option>
                        	<option value="db">db</option>
                        </select>
                    </div>
            </fieldset>
            </form>
            <hr/>
            <iframe id="frameDAOSetup" style="width:100%;height: 100%">
            </iframe>
        </td>
        <td style="width: 20%"></td>
    </tr>
    <tr style="height: 60px">
        <td></td>
        <td></td>
        <td></td>
    </tr>
	</table>
	<script type="text/javascript">
<?php 
    if ($configuration != null){?>
        $("#selectDAO").val("<?php echo $configuration->dao ?>");
        onDAOChange();
<?php }
?>
	</script>
</div>
</body>
</html>