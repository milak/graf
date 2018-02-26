<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="">
<meta name="author" content="milak">
<link rel="icon" href="images/favicon.ico">
<title>GRAF - Graphic Rendering Architecture Framework</title>
<link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.css">
<link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/callout.css">
<link rel="stylesheet" type="text/css" href="vendor/jquery-panel/css/jquery-panel.css" />
<link rel="stylesheet" type="text/css" href="vendor/jquery/ui/1.12.1/themes/base/jquery-ui.css">
<link rel="stylesheet" type="text/css" href="vendor/DataTables/datatables.min.css" />
<link rel="stylesheet" type="text/css" href="vendor/DataTables/jquery.dataTables.min.css" />
<link rel="stylesheet" type="text/css" href="vendor/font-awsome/css/font-awesome.min.css" />
<style type="text/css">
html {
	height: 100%;
	overflow: hidden;
}
/** Styles liés à typeAHead */
.tt-menu { //
	width: 80px;
	margin: 5px 0;
	padding: 8px 0;
	background-color: #fff;
	border: 1px solid #ccc;
	border: 1px solid rgba(0, 0, 0, 0.2);
	-webkit-border-radius: 4px;
	-moz-border-radius: 4px;
	border-radius: 4px;
	-webkit-box-shadow: 0 5px 10px rgba(0, 0, 0, .2);
	-moz-box-shadow: 0 5px 10px rgba(0, 0, 0, .2);
	box-shadow: 0 5px 10px rgba(0, 0, 0, .2);
}

.tt-suggestion {
	padding: 3px 20px;
}

.tt-suggestion:hover {
	cursor: pointer;
	color: #fff;
	background-color: #0097cf;
}

.tt-suggestion.tt-cursor {
	color: #fff;
	background-color: #0097cf;
}
</style>
</head>
<body oncontextmenu="event.preventDefault()" onresize="item.refresh()">
	<header>
		<!-- Fixed navbar -->
		<nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
			<a class="navbar-brand" href="#" onClick="breadCrumb.home()" title="Graphic Rendering Architecture Framework">GRAF</a>
			<div class="collapse navbar-collapse" id="navbarCollapse">
				<ul class="navbar-nav mr-auto">
					<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" id="menuItem" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span
							data-i18n="menu.item" /></a>
						<div class="dropdown-menu" aria-labelledby="menuItem">
							<a class="dropdown-item" href="#" onClick="searchItem()"><img src="images/65.png" /> <span data-i18n="menu.search" /></a> <a class="dropdown-item" href="#"
								onClick="createItem()"><img src="images/78.png" /> <span data-i18n="menu.create" /></a> <a class="dropdown-item disabled" href="#" id="menuDeleteItem"
								onClick="global.item.delete()" disabled="true"><img src="images/33.png" /> <span data-i18n="menu.delete" /></a>
						</div></li>
					<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" id="menuDocument" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span
							data-i18n="menu.document" /></a>
						<div class="dropdown-menu" aria-labelledby="menuDocument">
							<a class="dropdown-item" href="#" onClick="searchDocument()"><img src="images/65.png" /> <span data-i18n="menu.search" /></a>
							<a class="dropdown-item" href="#" onClick="createDocument()"><img src="images/78.png" /> <span data-i18n="menu.create" /></a>
							<a class="dropdown-item disabled" href="#" id="menuDeleteDocument"
								onClick="global.document.delete()" disabled="true"><img src="images/33.png" /> <span data-i18n="menu.delete" /></a>
						</div></li>
					<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" id="menuView" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span
							data-i18n="menu.views" /></a>
						<div class="dropdown-menu" id="menuViewDropDown" aria-labelledby="menuView"></div></li>
					<li class="nav-item">
						<ol class="breadcrumb black" id="breadcrumb" style="background-color: transparent; padding: 0px; margin: 0px; margin-top: 8px">
							<li class="breadcrumb-item active"><span data-i18n="breadcrumb.no_item" /></li>
						</ol>
					</li>
				</ul>
				<form class="form-inline mt-2 mt-md-0">
					<div>
						<input class="typeahead form-control mr-sm-2" type="text" id="menuSearchInput" placeholder="Search" aria-label="Search">
					</div>
					<!--button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button-->
				</form>
			</div>
		</nav>
	</header>
	<!-- Begin page content -->
	<main id="main" style="position:absolute;top:60px;width:100%; bottom:0px;"> </main>
	<div id="alert" class="alert alert-warning" style="position: absolute; display: none; bottom: 10px; left: 10px; right: 10px; vertical-align: center" role="alert">
		<span id="alertBadge" class="badge" style="padding: 8px; margin-right: 15px"> <img id="alertIcon" /> <strong id="alertLevel" />
		</span> <span id="alertMessage"></span>
	</div>
	<svg id="strategic" style="width: 100%; height: 100%; display: none"></svg>
	<svg id="business" style="width: 100%; height: 100%; display: none"></svg>
	<svg id="logical" style="width: 100%; height: 100%; display: none"></svg>
	<svg id="service" style="width: 100%; height: 100%; display: none"></svg>
	<svg id="process" style="width: 100%; height: 100%; display: none"></svg>
	<svg id="technical" style="width: 100%; height: 100%; display: none"></svg>
	<div id="viewItem" style="width: 100%; height: 100%; display: none"></div>
	<svg id="mapEurope" style="width: 100%; height: 100%; display: none"></svg>
	<svg id="mapWorld" style="width: 100%; height: 100%; display: none"></svg>
	<div id="searchItem" style="display: none"></div>
	<div id="createItem" style="display: none"></div>
	<div id="popup" style="display: none"></div>

	<!-- ========================================================== -->
	<!-- Placed at the end of the document so the pages load faster -->
	<script type="text/javascript" src="vendor/jquery/jquery-3.2.1.min.js"></script>
	<script type="text/javascript" src="vendor/jquery/ui/1.12.1/jquery-ui.js"></script>
	<script type="text/javascript" src="vendor/svgtool/svg-pan-zoom.js"></script>
	<script type="text/javascript" src="vendor/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="vendor/jquery/typeahead/typeahead.jquery.min.js"></script>
	<script type="text/javascript" src="vendor/jquery/typeahead/handlebars.js"></script>
	<script type="text/javascript" src="vendor/jquery-panel/js/jquery-panel.js"></script>
	<script type="text/javascript" src="vendor/DataTables/datatables.min.js"></script>
	<script type="text/javascript" src="vendor/jquery-i18next/i18next.min.js"></script>
	<script type="text/javascript" src="vendor/jquery-i18next/jquery-i18next.min.js"></script>
	<script type="text/javascript" src="vendor/graf/util.js"></script>
	<script type="text/javascript" src="vendor/graf/view.js"></script>
	<script type="text/javascript" src="vendor/graf/item.js"></script>
	<script type="text/javascript" src="vendor/graf/document.js"></script>
</body>
</html>