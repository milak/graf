<?php
// Réalise un test de connection et redirige éventuellement sur la page setup
require("dao/dao.php");
?>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>GRAF - Graphic Rendering Architecture Framework</title>
 	<link rel="stylesheet" href="css/graf.css">
 	<link rel="stylesheet" href="lib/pure/pure-min.css">
	<link rel="stylesheet" href="lib/jquery/ui/1.12.1/themes/base/jquery-ui.css">
	<link rel="stylesheet" href="lib/datatable/jquery.dataTables.min.css">
	<script type="text/javascript" src="lib/jquery/jquery-3.2.1.min.js"></script>
	<script type="text/javascript" src="lib/jquery/ui/1.12.1/jquery-ui.js"></script>
	<script type="text/javascript" src="lib/svgtool/svg-pan-zoom.js"></script>
	<script type="text/javascript" src="lib/nodeca/js-yaml.min.js"></script>
	<script type="text/javascript" src="lib/datatable/jquery.dataTables.min.js"></script>
	<script type="text/javascript" src="script/util.js"></script>
	<script type="text/javascript" src="script/strategic.js"></script>
	<script type="text/javascript" src="script/business.js"></script>
	<script type="text/javascript" src="script/process.js"></script>
	<script type="text/javascript" src="script/service.js"></script>
	<script type="text/javascript" src="script/logic.js"></script>
	<script type="text/javascript" src="script/actor.js"></script>
	<script type="text/javascript" src="script/view.js"></script>
	<script type="text/javascript" src="script/item.js"></script>
	<script>
		function displayTechnic(){
			hideToolBox();
			$("#technic_toolbox").show();
			clearFrame();
		}
	</script>
</head>
<body>
<div style="width:10%;height:100%;background-color:#5588EE; float: left;">
	<div style="width:100%;height:100px;text-align:center;color:white;vertical-align:middle;padding-top:10px">
		<div style="font-size:35px">GRAF</div>
		<div style="font-size:14px">Graphic Rendering<br/>Architect Framework</div>
	</div>
	<div class="menu" onclick="displayStrategic(null)">Vue strat&eacute;gique</div>
	<div class="menu" onclick="displayBusiness(null)">Vue métier</div>
	<div class="menu" onclick="displaySolution(null)">Vue logique</div>
	<div class="menu" onclick="displayService(null)">Vue service</div>
	<div class="menu" onclick="displayProcess(null)">Vue processus</div>
	<div class="menu" onclick="displayTechnic(null)">Vue technique</div>
	<div class="menu" onclick="displayViews(null)">Gestion des vues</div>
</div>
<div style="width:90%;height:100%;float:right">
	<table style="width:100%;height:100%;border:0px;border-collapse:collapse">
	<tbody>
		<tr>
		<td>
		<div id="default_toolbox" style="width:100%">
		</div>
		<div id="strategic_toolbox" style="width:100%;display:none" class="controlgroup">
			<button type="button" onclick="createDomain()"><img style="height:14px" src="images/78.png"/> domaine</button>
			<label for="strategic_viewprocess">Voir les processus</label>
			<input type="checkbox" name="strategic_viewprocess" id="strategic_viewprocess" onclick="strategic_checkSeeProcess()"/>
		</div>
		<div id="service_toolbox" style="width:100%;display:none" class="controlgroup">
			<button id="service_search_service_button" onclick='$("#search_service_form").dialog({"modal":true,"title":"Chercher un service","minWidth":500})'><img style="height:14px" src="images/65.png"/> chercher un service</button>
			<button id="service_create_device_button" 	title="Importer un matériel" 			onclick="createComponent('device')" 	disabled="true"><img style="height:14px" src="images/1633.png"/> matériel</button>
			<button id="service_create_data_button" 	title="Importer une donnée"				onclick="createComponent('data')" 		disabled="true"><img style="height:14px" src="images/1633.png"/> donnée</button>
			<button id="service_create_software_button" title="Importer un logiciel"			onclick="createComponent('software')" 	disabled="true"><img style="height:14px" src="images/1633.png"/> logiciel</button>
			<button id="service_create_service_button" 	title="Importer un service"				onclick="createComponent('service')" 	disabled="true"><img style="height:14px" src="images/1633.png"/> service</button>
			<button id="service_create_instance_button" title="Créer une instance de ce service" onclick="createServiceInstance()" 		disabled="true"><img style="height:14px" src="images/78.png"/> instance</button>
		</div>
		<div id="logic_toolbox" style="width:100%;display:none" class="controlgroup">
			<button id="logic_search_solution_button" 	onclick='$("#search_solution_form").dialog({"modal":true,"title":"Chercher une solution","minWidth":500})'><img style="height:14px" src="images/65.png"/> chercher une solution</button>
			<button id="logic_create_device_button" 	title="Importer un élément" 			onclick="openSearchItemForm()" 	disabled="true"><img style="height:14px" src="images/1633.png"/> élément</button>
			<button id="logic_create_instance_button" 	title="Créer une instance de ce service" onclick="createSolutionInstance()" 		disabled="true"><img style="height:14px" src="images/78.png"/> instance</button>
			<button id="logic_edit_button" 				onclick="editSolutionScript()" disabled="true"><img style="height:14px" src="images/63.png"/> editer</button>
		</div>
		<div id="process_toolbox" style="width:100%;display:none" class="controlgroup">
			<button id="process_search_process_button" 	onclick='searchProcess()'><img style="height:14px" src="images/65.png"/> chercher un processus</button>
			<button id="process_create_step_button" 	onclick="createProcessStep()" disabled="true"><img style="height:14px" src="images/78.png"/> étape</button>
			<button id="process_edit_button" 			onclick="editProcessScript()" disabled="true"><img style="height:14px" src="images/63.png"/> editer</button>
		</div>
		<div id="business_toolbox" style="width:100%;display:none" class="controlgroup">
			<button id="business_search_domain_button" 	onclick='$("#search_domain_form").dialog({"modal":true,"title":"Chercher un domain","minWidth":500})'><img style="height:14px" src="images/65.png"/> chercher un domaine</button>
			<button id="business_create_process_button" onclick="createProcess(currentDomainId)" disabled="true"><img style="height:14px" src="images/78.png"/> processus</button>
			<button id="business_create_service_button" onclick="createService()" disabled="true"><img style="height:14px" src="images/78.png"/> service</button>
			<button id="business_create_actor_button" 	onclick="createActor()" disabled="true"><img style="height:14px" src="images/78.png"/> acteur</button>
		</div>
		<div id="technic_toolbox" style="width:100%;vertical-align:middle;display:none" class="controlgroup">
			<label for="transmission-automatic">Automatic</label>
			<input type="checkbox" name="transmission-automatic" id="transmission-automatic"/>
		</div>
		<div id="views_toolbox" style="width:100%;display:none" class="controlgroup">
			<select id="viewSelected">
				<option value="null" selected>--choisir une vue--</option>
			</select>
			<label for="views_fill">Remplir</label>
			<input type="checkbox" name="views_fill" id="views_fill" onclick="views_checkFill()" checked/>
			<button id="views_update_button" onclick="updateView()" disabled="true"><img style="height:14px" src="images/96.png"/> update</button>
	      <!--label for="transmission-standard">Standard</label>
	      <input type="radio" name="transmission" id="transmission-standard">
	      <label for="transmission-automatic">Automatic</label>
     	 <input type="radio" name="transmission" id="transmission-automatic">
      <label for="insurance">Insurance</label>
      <input type="checkbox" name="insurance" id="insurance">
      <label for="horizontal-spinner" class="ui-controlgroup-label"># of cars</label>
      <input id="horizontal-spinner" class="ui-spinner-input">
      <button>Book Now!</button-->
		</div>
	</td>
	</tr>
	<tr style="height:100%">
	<td>
		<svg id="frame" style="width:100%;height:100%"></svg>
	</td>
	</tr>
	</tbody>
	</table>
</div>
<div id="process_script_editor_form" style="display:none">
	<div style="position: absolute; top: 5px;      bottom: 45px;      left: 0;      right: 0;">
		<textarea id="process_script_editor_form_text" style="border:4px groove black;width:100%;height:100%;resize: none;"></textarea>
	</div>
	<div style="position: absolute; bottom: 0px;      left: 0;      right: 0;">
		<button onclick="saveProcessScript(currentProcessId)"><img style="height:14px" src="images/78.png"/> enregistrer</button>
		<button onclick="loadProcessScript(currentProcessId)"><img style="height:14px" src="images/33.png"/> annuler les changements</button>
	</div>
</div>
<div id="solution_script_editor_form" style="display:none;width: 100%;height: 100%">
	<div style="position: absolute; top: 5px;      bottom: 45px;      left: 0;      right: 0;">
		<textarea id="solution_script_editor_form_text" style="border:4px groove black;width:100%;height:100%;resize: none;"></textarea>
	</div>
	<div style="position: absolute; bottom: 0px;      left: 0;      right: 0;">
		<button onclick="saveSolutionScript(currentSolutionId)"><img style="height:14px" src="images/78.png"/> enregistrer</button>
		<button onclick="loadSolutionScript(currentSolutionId)"><img style="height:14px" src="images/33.png"/> annuler les changements</button>
	</div>
</div>
<div id="popup" style="display:none">
</div>
<form id="update_view_form" style="display:none" class="pure-form pure-form-aligned">
	<fieldset>
		<div class="pure-control-group">
			<label for="update_view_form_name">Nom</label>
			<input id="update_view_form_name" type="text" readonly="true"/><br/>
		</div>
		<div class="pure-control-group">
			<label for="update_view_form_value">Valeur</label>
			<textarea id="update_view_form_value" style="width:100%;height:80%" rows="10">
			</textarea>
		</div>
	</fieldset>
	<hr/>
	<button type="button" onclick='doUpdateView()'><img style="height:14px" src="images/78.png"/> valider</button>
	<button type="button" onclick='$("#update_view_form").dialog("close");'><img style="height:14px" src="images/33.png"/> annuler</button>
</form>
<form id="create_actor_form" style="display:none" class="pure-form pure-form-aligned">
	<div class="pure-control-group">
		<label for="create_actor_form_name">Nom</label>
		<input type="text" 	id="create_actor_form_name" 		name="name"/>
	</div>
	<div class="pure-control-group">
		<label for="create_actor_form_class">Classe</label>
		<select id="create_actor_form_class"></select>
	</div>
	<hr/>
	<button type="button" onclick='doCreateActor()'><img style="height:14px" src="images/78.png"/> créer</button>
	<button type="button" onclick='$("#create_actor_form").dialog("close");'><img style="height:14px" src="images/33.png"/> annuler</button>
</form>
<form id="create_domain_form" style="display:none" class="pure-form pure-form-aligned">
	<div class="pure-control-group">
		<label for="create_domain_form_name">Nom</label>
		<input type="text" 	id="create_domain_form_name"/>
	</div>
	<div class="pure-control-group">
		<label for="create_domain_form_area">Zone</label>
		<select id="create_domain_form_area"></select>
	</div>
	<hr/>
	<button type="button" onclick='doCreateDomain()'><img style="height:14px" src="images/78.png"/> cr&eacute;er</button>
	<button type="button" onclick='$("#create_domain_form").dialog("close");'><img style="height:14px" src="images/33.png"/> annuler</button>
</form>
<form id="create_component_form" style="display:none" class="pure-form pure-form-aligned">
	<input type="hidden" id="create_component_form_type"/>
	<div id="create_component_form_service" class="pure-control-group">
		<label for="create_component_form_service_list">Service</label>
		<select id="create_component_form_service_list"></select>
	</div>
	<div id="create_component_form_software" class="pure-control-group">
		<label for="create_component_form_software_list">Logiciel</label>
		<select id="create_component_form_software_list"></select>
		<span class="pure-form-message-inline">
			<button title="Créer un logiciel" onclick="createSoftware()"><img style="height:14px" src="images/78.png"/></button>
		</span>
	</div>
	<div id="create_component_form_data" class="pure-control-group">
		<label for="create_component_form_data_list">Donnée</label>
		<select id="create_component_form_data_list"></select>
		<span class="pure-form-message-inline">
			<button title="Créer une donnée"  onclick="createData()"><img style="height:14px" src="images/78.png"/></button>
		</span>
	</div>
	<div id="create_component_form_device" class="pure-control-group">
		<label for="create_component_form_device_list">Matériel</label>
		<select id="create_component_form_device_list"></select>
		<span class="pure-form-message-inline">
			<button title="Créer un matériel" onclick="createDevice()"><img style="height:14px" src="images/78.png"/></button>
		</span>
	</div>
	<div class="pure-control-group">
		<label for="create_component_form_area">Zone</label>
		<select id="create_component_form_area"></select>
	</div>
	<hr/>
	<button type="button" onclick='doCreateComponent()'><img style="height:14px" src="images/78.png"/> ajouter</button>
	<button type="button" onclick='$("#create_component_form").dialog("close");'><img style="height:14px" src="images/33.png"/> annuler</button>
</form>
<form id="edit_component_form" style="display:none" class="pure-form pure-form-aligned">
	<fieldset>
	<legend>Composant</legend>
		<div class="pure-control-group">
			<label for="edit_component_form_name">Nom</label>
			<input type="text" id="edit_component_form_name" readonly="true"/>
		</div>
		<div class="pure-control-group">
			<label for="edit_component_form_area">Zone</label>
			<select id="edit_component_form_area" onchange="onEditComponentFormAreaChanged()"></select>
		</div>
	</fieldset>
	<input type="hidden" id="edit_component_form_area_orig_value"/>
	<input type="hidden" id="edit_component_form_component_id"/>
	<hr/>
	<fieldset>
		<legend>Liens</legend>
		<table style="width:100%" class="pure-table">
			<thead>
				<tr>
					<th>Composant</th><th>Protocole</th><th>Port</th><th></th>
				</tr>
			</thead>
			<tbody id="edit_component_form_links"></tbody>
		</table>
	</fieldset>
	<div id="edit_component_form_toggle1" style="width: 100%;text-align: right;">
		<a href="#" onclick="$('#edit_component_form_toggle1').hide();$('#edit_component_form_toggle2').show();">Ajouter un lien</a>
	</div>
	<div id="edit_component_form_toggle2" style="display:none">
		<fieldset>
			<legend>Nouveau lien</legend>
		<div class="pure-control-group">
			<label  for="edit_component_form_service_list">Service</label>
			<select id ="edit_component_form_service_list"></select>
		</div>
		<div class="pure-control-group">
			<label  for="edit_component_form_component_list">Composant</label>
			<select id ="edit_component_form_component_list"></select>
		</div>
		<div class="pure-control-group">
			<label for="edit_component_form_protocole">Protocole</label>
			<input id ="edit_component_form_protocole" type='text'/>
		</div>
		<div class="pure-control-group">
			<label for="edit_component_form_port">Port</label>
			<input id ="edit_component_form_port" type='text'/>
		</div>
		<div id="edit_component_form_toggle1" style="width: 100%;text-align: right;">
			<a href="#" onclick="addComponentLink()">Valider</a>
			<a href="#" onclick="hideAddComponentLinkForm()">Annuler</a>
		</div>
		</fieldset>
	</div>
	<hr/>
	<button type="button" onclick='$("#edit_component_form").dialog("close");displayComponent(currentComponentId)' id="edit_component_form_ouvrir"><img src='images/63.png'/> ouvrir</button>
	<button type="button" onclick='$("#edit_component_form").dialog("close");unlinkComponent(currentComponentId)'><img src='images/14.png'/> retirer</button>
	<button type="button" onclick='$("#edit_component_form").dialog("close");updateComponent()' id="edit_component_form_valider" disabled="true"><img src='images/93.png'/> valider</button>
	<button type="button" onclick='$("#edit_component_form").dialog("close");'><img src='images/33.png'/> fermer</button>
</form>
<form id="process_create_form" class="pure-form pure-form-aligned" style="display:none">
	<fieldset>
		<div class="pure-control-group">
			<label for="process_create_form_name">Nom</label>
			<input type="text" 	id="process_create_form_name" 			name="name"/>
		</div>
		<div class="pure-control-group">
			<label for="process_create_form_description">Description</label>
			<textarea 		id="process_create_form_description" 	name="description"></textarea>
		</div>
		<input type="hidden" 	id="process_create_form_domain_id" 		name='domain_id' value=""/>
	</fieldset>
	<hr/>
	<button type="button" onclick='doCreateProcess()'><img style="height:14px" src="images/78.png"/> créer</button>
	<button type="button" onclick='$("#process_create_form").dialog("close");'><img style="height:14px" src="images/33.png"/> annuler</button>
</form>
<form id="process_step_create_form" class="pure-form pure-form-aligned" style="display:none">
	<input type="hidden" 	id="process_step_create_form_process_id"/>
	<fieldset>
		<div class="pure-control-group">
			<label for="process_step_create_form_name">Nom</label>
			<input type="text" 		id="process_step_create_form_name" onchange="onProcessStepCreateFormNameChange()"/><br/>
		</div>
		<div class="pure-control-group">
			<label for="process_step_create_form_type">Type</label>
			<select id="process_step_create_form_type" onchange="onProcessStepCreateFormTypeChange()">
				<option value="null">~~choisir un type~~</option>
				<option value="SERVICE">Service</option>
				<option value="ACTOR">Acteur</option>
				<option value="SUB-PROCESS">Sous processus</option>
				<option value="CHOICE">Choix</option>
				<option value="END">Fin</option>
			</select>
		</div>
		<div id="process_step_create_form_actor" pistyle="display:none" class="pure-control-group">
			<label for="process_step_create_form_actor_list">Acteur</label>
			<select id="process_step_create_form_actor_list" onchange="onProcessStepCreateFormActorListChange()"></select>
		</div>
		<div id="process_step_create_form_service" pistyle="display:none" class="pure-control-group">
			<label for="process_step_create_form_service_list">Service</label>
			<select id="process_step_create_form_service_list" onchange="onProcessStepCreateFormServiceListChange()"></select>
		</div>
		<div id="process_step_create_form_process" pistyle="display:none" class="pure-control-group">
			<label for="process_step_create_form_process_list">Processus</label>
			<select id="process_step_create_form_process_list" onchange="onProcessStepCreateFormProcessListChange()"></select>
		</div>
	</fieldset>
	<hr/>
	<button type="button" onclick='doCreateProcessStep()' id="process_step_create_form_submit" disabled="true"><img style="height:14px" src="images/78.png"/> créer</button>
	<button type="button" onclick='$("#process_step_create_form").dialog("close");'><img style="height:14px" src="images/33.png"/> annuler</button>
</form>
<form id="edit_process_step_form" class="pure-form pure-form-aligned" style="display: none;">
	<input type="hidden"	id="edit_process_step_form_id"/>
	<input type="hidden"	id="edit_process_step_form_sub_process_id"/>
	<input type="hidden"	id="edit_process_step_form_actor_id"/>
	<input type="hidden"	id="edit_process_step_form_service_id"/>
	<fieldset>
		<legend>Etape</legend>
		<div class="pure-control-group">
			<label for="edit_process_step_form_name">Nom</label>
			<input type="text" 		id="edit_process_step_form_name" readonly="true"/>
		</div>
		<div class="pure-control-group">
			<label for="edit_process_step_form_type">Type</label>
			<input type="text" 		id="edit_process_step_form_type" readonly="true"/>
		</div>
	</fieldset>
	<fieldset>
		<legend>Liens</legend>
		<table style="width:100%" class="pure-table">
			<thead><tr><td>Etape</td><td>Label</td><td></td></tr></thead>
			<tbody id="edit_process_step_form_link_list"></tbody>
		</table>
	</fieldset>
	
	<div id="edit_process_step_form_toggle1" style="width: 100%;text-align: right;">
		<a href="#" onclick="$('#edit_process_step_form_toggle1').hide();$('#edit_process_step_form_toggle2').show();">Ajouter un lien</a>
	</div>
	<div id="edit_process_step_form_toggle2" style="display:none">
		<fieldset>
			<legend>Nouveau lien</legend>
		<div class="pure-control-group">
			<label  for="edit_process_step_form_step_list">Etape</label>
			<select id ="edit_process_step_form_step_list"></select>
		</div>
		<div class="pure-control-group">
			<label for="edit_process_step_form_label">Label</label>
			<input id ="edit_process_step_form_label" type='text'/>
		</div>
		<div class="pure-control-group">
			<label for="edit_process_step_form_data">Donnée</label>
			<input id ="edit_process_step_form_data" type='text'/>
		</div>
		<div id="edit_component_form_toggle1" style="width: 100%;text-align: right;">
			<a href="#" onclick="addProcessStepLink()">Valider</a>
			<a href="#" onclick="$('#edit_process_step_form_toggle2').hide();$('#edit_process_step_form_toggle1').show();">Annuler</a>
		</div>
		</fieldset>
	</div>
	<hr/>
	<button type="button" onclick='$("#edit_process_step_form").dialog("close");displayProcess($("#edit_process_step_form_sub_process_id").val())' style="display:none" id="edit_process_step_form_open_process">
		<img style="height:14px" src='images/63.png'/> ouvrir
	</button>
	<button type="button" onclick='$("#edit_process_step_form").dialog("close");deleteProcessStep($("#edit_process_step_form_id").val())' style="display:none" id="edit_process_step_form_delete">
		<img style="height:14px" src='images/14.png'/> supprimer
	</button>
	<button type="button" onclick='updateProcessStep()' id="edit_process_step_form_submit" disabled="true">
		<img style="height:14px" src="images/78.png"/> valider
	</button>
	<button type="button" onclick='$("#edit_process_step_form").dialog("close");'>
		<img style="height:14px" src="images/33.png"/> annuler
	</button>
</form>
<form id="edit_item_form" class="pure-form pure-form-aligned" style="display:none">
	<fieldset>
		<input type="hidden"		id="edit_item_form_target_id"/>
		<div class="pure-control-group">
			<label for="edit_item_form_name">Nom</label>
			<input type="text" 		id="edit_item_form_name"/>
		</div>
		<div class="pure-control-group">
			<label for="edit_item_form_type">Type</label>
			<input type="text" id="edit_item_form_type"/>
		</div>
		<div class="pure-control-group">
			<label for="edit_item_form_class">Classe</label>
			<input type="text" id="edit_item_form_class"/>
		</div>
		<div class="pure-control-group">
			<label for="edit_item_form_category">Catégorie</label>
			<input type="text" id="edit_item_form_category"/>
		</div>
		<label>Propriétés :</label><br/><br/>
		<table class="pure-table" style="width: 100%;left:50px;right:50px">
			<thead>
				<tr><th>Clé</th><th>Valeur</th></tr>
			</thead>
			<tbody id="edit_item_form_properties"></tbody>
		</table>
		<br/>
	</fieldset>
	<hr/>
	<button type="button" id="edit_item_form_display_target" onclick='$("#edit_item_form").dialog("close");showToscaTargetItem($("#edit_item_form_target_id").val(),$("#edit_item_form_category").val())' style="display:none"><img src='images/63.png'/> ouvrir</button>
	<button type="button" onclick='$("#edit_item_form").dialog("close");deleteToscaItem($("#edit_item_form_name").val())'><img src='images/14.png'/> supprimer</button>
	<button type="button" onclick='$("#edit_item_form").dialog("close");'><img src='images/33.png'/> fermer</button>
</form>
<form id="search_item_form" class="pure-form pure-form-aligned" style="display:none">
	<table style="width: 100%">
		<tr>
		<td>
    		<fieldset>
        		<legend>Chercher des éléments</legend>
        		<div class="pure-control-group">
        			<label for="search_item_form_name">Nom</label>
        			<input type="text" 		id="search_item_form_name"/>
        		</div>
        		<div class="pure-control-group">
        			<label for="search_item_form_category">Catégorie</label>
        			<select id="search_item_form_category" onChange="onSearchItemFormCategoryChange()"></select>
        		</div>
        		<div class="pure-control-group">
        			<label for="search_item_form_class">Classe</label>
        			<select id="search_item_form_class"></select>
        		</div>
        		<button type="button" onClick="onSearchItemFormSearchClick()"><img style="height:14px" src="images/65.png"/> chercher</button>
        	</fieldset>
		</td>
		<td>
			<div class="pure-control-group">
        		<label for="search_item_form_area">Zone où sera ajouté l'élément</label>
        		<select id="search_item_form_area"></select>
        	</div>
		</td>
		</tr>
	</table>
	<hr/>
	<table style="width: 100%" id="search_item_form_result">
		<thead style="width:100%">
			<tr>
				<td>&Eacute;l&eacute;ment</td>
				<td>Classe</td>
				<td>Cat&eacute;gorie</td>
				<td>Action</td>
			</tr>
		</thead>
		<tbody>
		</tbody>
	</table>
</form>
<form id="search_domain_form" class="pure-form pure-form-aligned" style="display:none">
	<div class="pure-control-group">
		<label for="search_domain_form_list">Domaine</label>
		<select id="search_domain_form_list"></select>
	</div>
	<hr/>
	<button type="button" onclick='$("#search_domain_form").dialog("close");searchDomain();'><img style="height:14px" src="images/93.png"/> valider</button>
	<button type="button" onclick='$("#search_domain_form").dialog("close");'><img style="height:14px" src="images/33.png"/> annuler</button>
</form>
<form id="search_process_form" class="pure-form pure-form-aligned" style="display:none">
	<div class="pure-control-group">
		<label for="search_process_form_domain_list">Domaine</label>
		<select id="search_process_form_domain_list" onchange="onSearchProcessFormDomainListChange()"></select>
	</div>
	<div class="pure-control-group">
		<label for="search_process_form_process_list">Processus</label>
		<select id="search_process_form_process_list"></select>
	</div>
	<hr/>
	<button type="button" onclick='$("#search_process_form").dialog("close");doSearchProcess();'><img style="height:14px" src="images/93.png"/> valider</button>
	<button type="button" onclick='$("#search_process_form").dialog("close");'><img style="height:14px" src="images/33.png"/> annuler</button>
</form>
<form id="search_service_form" class="pure-form pure-form-aligned" style="display:none">
	<div class="pure-control-group">
		<label for="search_service_form_list">Service</label>
		<select id="search_service_form_list"></select>
	</div>
	<hr/>
	<button type="button" onclick='$("#search_service_form").dialog("close");searchService();'><img style="height:14px" src="images/93.png"/> valider</button>
	<button type="button" onclick='$("#search_service_form").dialog("close");'><img style="height:14px" src="images/33.png"/> annuler</button>
</form>
<form id="search_solution_form" class="pure-form pure-form-aligned" style="display:none">
	<div class="pure-control-group">
		<label for="search_solution_form_list">Solution</label>
		<select id="search_solution_form_list"></select>
	</div>
	<hr/>
	<button type="button" onclick='$("#search_solution_form").dialog("close");searchSolution();'><img style="height:14px" src="images/93.png"/> valider</button>
	<button type="button" onclick='$("#search_solution_form").dialog("close");'><img style="height:14px" src="images/33.png"/> annuler</button>
</form>
<div id="create_service_form" style="display:none">
	Nom : 	<input type="text"		id="create_service_form_name" 		name="name"/><br/>
	Code :	<input type="text"		id="create_service_form_code" 		name="code"/><br/>
			<input type="hidden" 	id="create_service_form_domain_id" 	name='domain_id'/>
	<hr/>
	<button type="button" onclick='doCreateService()'><img style="height:14px" src="images/78.png"/> cr&eacute;er</button>
	<button type="button" onclick='$("#create_service_form").dialog("close");'><img style="height:14px" src="images/33.png"/> annuler</button>
</div>
<form id="create_instance_form" style="display:none" class="pure-form pure-form-aligned">
	<input type="hidden" 	id="create_instance_form_service_id" 	name='service_id'/>
	<fieldset>
		<div class="pure-control-group">
			<label for="create_instance_form_name">Nom</label>
			<input type="text"		id="create_instance_form_name" 		name="name"/>
		</div>
		<div class="pure-control-group">
			<label for="create_instance_form_environment">Environment</label>
			<select id="create_instance_form_environment"></select>
		</div>
	</fieldset>
	<hr/>
	<button type="button" onclick='doCreateServiceInstance()'><img style="height:14px" src="images/78.png"/> cr&eacute;er</button>
	<button type="button" onclick='$("#create_instance_form").dialog("close");'><img style="height:14px" src="images/33.png"/> annuler</button>
</form>
<script>
	function refreshEnvironmentList(){
		$.getJSON("api/environment.php", function(result){
			var environments = result.environments;
			var options = "<option value='null' selected>--choisir un environement--</option>";
			for (var i = 0; i < environments.length; i++){
				var environment = environments[i];
				options += '<option value="'+environment.id+'">'+environment.name+'</option>';
			}
			$('#create_instance_form_environment').html(options);
		}).fail(function(jxqr,textStatus,error) {
			showPopup("Echec","<h1>Impossible de charger les environnements</h1>"+textStatus+ " : " + error);
		});
	}
	$( function() {
	 	$(".controlgroup" ).controlgroup();
		$.getJSON( "api/view.php", function(result) {
			var views = result.views;
			var options = "";
			for (var i = 0; i < views.length; i++){
				var view = views[i];
				options += '<option value="'+view.name+'">'+view.name+'</option>';
			}
			$('#viewSelected').append(options);
  		}).fail(function(jxqr,textStatus,error) {
			showPopup("Echec","<h1>Impossible de charger les vues</h1>"+textStatus+ " : " + error);
		});
		$("#viewSelected").on("selectmenuchange", selectView);
		$("#edit_component_form_service_list").on("change", function(){
			var serviceId = $("#edit_component_form_service_list").val();
			if (serviceId == "null"){
				serviceId = currentServiceId;
			}
			filterComponentList(serviceId);
		});
		refreshDomainList();
		refreshEnvironmentList();
		refreshAreaList();
		refreshServiceList();
		refreshActorLists();
		refreshProcessLists();
		refreshSolutionLists();
		refreshLogicalAreaList();
	 });
</script>
</body>
</html>