(function ($) {
  Drupal.behaviors.colloquiumBehavior = {
    attach: function (context, settings) {
      //alert("Hello World");
      var event = "";
      var url_path = window.location.pathname;
      //console.log(url_path);  
      //alert(url_path);  
      if(url_path.substring(0, 46).toLowerCase() == '/webform/cct_fyi_colloquium_2019_registra/test'
      	&& url_path.substring(0, 50).toLowerCase() != '/cct/fyi-colloquium-2019/registration/confirmation') {
         //console.log("setting up for colloquium");
	       $.setupDivisionChangeEventListener(); 
      }

      if(url_path.substring(0, 37).toLowerCase() == '/cct/fyi-colloquium-2019/registration' && url_path.substring(0, 50).toLowerCase() != '/cct/fyi-colloquium-2019/registration/confirmation') {
          //console.log("setting up for colloquium r");
          $.setupDivisionChangeEventListener();
      }
      if(url_path.substring(0, 30).toLowerCase() == '/cct/sssc-retreat/registration' && url_path.substring(0, 50).toLowerCase() != '/cct/sssc-retreat/registration/confirmation') {
          //console.log("sssc-retreat");
          $.setupDivisionChangeEventListener();
      }
      if(url_path.substring(0, 45).toLowerCase() == '/cct/fyi-colloquium-2019/registration' && url_path.substring(0, 50).toLowerCase() != '/cct/fyi-colloquium-2019/abstract-submissions') {
	    //$.setupAbstractCategoryChangeEventListener();
      }

    }
  }

  var userinfo_server = "https://userinfo-dev.nci.nih.gov/api/org/";
  var branches_json = {HNC7Z36:"LPDS - Laboratory of Protein Dynamics & Signaling",HNC7Z37:"MIP - Molecular Imaging Program",HNC7Z30:"NOB - Neuro-oncology Branch",HNC7Z38:"VB - Vaccine Branch",HNC7R:"LEC - Laboratory of Experimental Carcinogenesis",HNC7Z02:"DTB - Developmental Therapeutics Branch",HNC7Z05:"LM - Laboratory of Metabolism",HNC7Z04:"LBMB - Laboratory of Biochemistry & Molecular Biology",HNC7Z07:"HIVDRP - Hiv Dynamics & Replication Program",HNC7Z06:"LRBGE - Laboratory of Receptor Biology & Gene Expression",HNC7Z32:"MCL - Macromolecular Crystallography Laboratory",HNC7Z18:"LP - Laboratory of Pathology",HNC7Z19:"POB - Pediatric Oncology Branch",HNC7Z17:"SB - Surgery Branch",HNC7Z12:"CDBL - Cancer & Developmental Biology Laboratory",HNC7Z11:"MCGP - Mouse Cancer Genetics Program",HNC7Z26:"RBB - Radiation Biology Branch",HNC7Y:"LICB - Laboratory of Immune Cell Biology",HNC7Q:"LCCTP - Laboratory of Cellular Carcinogenesis and Tumor Promotion",HNC7K:"LHC - Laboratory of Human Carcinogenesis",HNC7J:"LCMB - Laboratory of Cellular & Molecular Biology",HNC7L:"LGD - Laboratory of Genomic Diversity",HNC7Z56:"TGMB - Thoracic & Gi Malignancies Branch",HNC7B:"LMB - Laboratory of Molecular Biology",HNC7Z41:"LCDS - Laboratory of Cell & Developmental Signaling",HNC7Z43:"LCBG - Laboratory of Cancer Biology & Genetics",HNC79:"LCB - Laboratory of Cell Biology",HNC78:"LCO - Laboratory of Cellular Oncology",HNC71:"OD - Office of the Director",HNC72:"EIB - Experimental Immunology Branch",HNC74:"LTIB - Laboratory of Tumor Immunology & Biology",HNC7Z53:"TOSB - Thoracic & Oncologic Surgery Branch",HNC7Z50:"LGI - Laboratory of Genome Integrity",HNC7Z54:"WMB - Womens Malignancies Branch",HNC7Z55:"GMB - Genitourinary Malignancies Branch",HNC7Z16:"LYMB - Lymphoid Malignancies Branch",HNC7Z52:"EOB - Endocrine Oncology Branch",HNC7Z42:"CIP - Cancer & Inflammation Program",HNC7Z27:"HAMB - Hiv & Aids Malignancy Branch",HNC7Z25:"LGCP - Laboratory of Genitourinary Cancer Pathogenesis",HNC7Z24:"CGB - Cancer Genetics Branch",HNC7Z23:"ETIB - Experimental Transplantation & Immunology Branch",HNC7Z20:"ROB - Radiation Oncology Branch",HNC7Z29:"CPSB - Cancer Prevention Studies Branch",HNC7Z28:"UOB - Urologic Oncology Branch",HNCC6:"DCAB - Dna & Chromosome Aberrations Branch",HNCC1:"OD - Office of the Director",HNCCE:"TMB - Tumor Metastasis Branch",HNCCD:"CIHEB - Cancer Immunology, Hematology & Etiology Branch",HNCCB:"CCBB - Cancer Cell Biology Branch",HNCCA:"TBMB - Tumor Biology & Microenvironment Branch",HNCCC:"SBMAB - Structural Biology & Molecular Applications Branch",HNCD3:"BRP - Behavioral Research Program",HNCD1:"OD - Office of the Director",HNCD4:"SRP - Surveillance Research Program",HNCD5:"HDRP - Healthcare Delivery Research Program",HNCD2:"EGRP - Epidemiology & Genomics Research Program",HNC9C:"EBP - Epidemiology & Biostatistics Program",HNC91:"OD - Office of the Director",HNC9B:"HGP - Human Genetics Program",HNC41:"OD - Office of the Director",HNC49:"GOCRG - Gastrointestinal & Other Cancer Research Group",HNC4B:"CADRG - Chemopreventive Agent Development Research Group",HNC4D:"NSRG - Nutritional Science Research Group",HNC4G:"CBRG - Cancer Biomarkers Research Group",HNC4H:"EDRG - Early Detection Research Group",HNC4J:"BRG - Biometry Research Group",HNC42:"BGCRG - Breast & Gynecologic Cancer Research Group",HNC43:"PUCRG - Prostate & Urologic Cancer Research Group",HNC44:"LUACRG - Lung & Upper Aerodigestive Cancer Research Group",HNC4C:"COPTRG - Community Oncology & Prevention Trials Research Group",HNCB6:"CDP - Cancer Diagnosis Program",HNCB2:"RRP - Radiation Research Program",HNCB1:"OD - Office of the Director",HNCB8:"CIP - Cancer Imaging Program",HNCBA:"BRP - Biometric Research Program",HNCB3:"CTEP - Cancer Therapy Evaluation Program",HNCB9:"TRP - Translational Research Program",HNCB4:"DTP - Developmental Therapeutics Program",HNC53:"ORRPC - Office of Referral, Review, & Program Coordination",HNC52:"OEA - Office of Extramural Applications",HNC51:"OD - Office of the Director",HNC1R:"CRS - Center For Research Strategy",HNC1Q:"CCT - Center For Cancer Training",HNC1P:"CCG - Center For Cancer Genomics",HNC1K:"OHAM - Office of Hiv & Aids Malignancy",HNC1H:"NCIFOSO - Nci Frederick Office of Scientific Operations",HNC1N:"CGH - Center For Global Health",HNC1M:"OCC - Office of Cancer Centers",HNC14:"OCPL - Office of Communications and Public Liaison",HNC17:"OM - Office of Management",HNC1E:"CRCHD - Center To Reduce Cancer Health Disparities",HNC1D:"CBIIT - Center For Biomedical Informatics & Information Technology",HNC1J:"SBIR - Small Business Innovation Research (sbir) Development Center",HNC1L:"CSSI - Center For Strategic Scientific Initiatives",HNC16:"OSPA - Office of Science Planning & Assessment",HNC1D4:"CIB - Cancer Informatics Branch",HNC1D5:"ESIB - Evaluation & Strategic Initiatives Branch",HNC1D2:"IIOB - Infrastructure & It Operations  Branch",HNC1D6:"ODS - Office of Data Sharing",HNC1D3:"BOB - Business Operations Branch",HNC1P2:"OCG - Office of Cancer Genomics",HNC1P3:"TCGA - the Cancer Genome Atlas Program Office",HNC1Q2:"CTB - Cancer Training Branch",HNC1Q4:"OTE - Office of Training & Education",HNC1Q3:"IDWB - Intramural Diversity Workforce Branch",HNC1E4:"DTB - Diversity Training Branch",HNC1E2:"INB - Integrated Networks Branch",HNC1L3:"OCNR - Office of Cancer Nanotechnology Research",HNC1L2:"OPSO - Office of Physical Sciences-oncology",HNC1L4:"OCCPR - Office of Cancer Clinical Proteomics Research",HNC1L6:"OBBR - Office of Biorepositories & Biospecimen Research",HNC14P:"OPA - Office of Public Affairs",HNC14T:"OCC - Office of Cancer Content",HNC141:"OD - Office of the Director",HNC14Q:"ODDC - Office of Dissemination & Digital Communications",HNC17H:"OEFIA - Office of Extramural Finance & Information Analysis",HNC17J:"OGCR - Office of Government & Congressional Relations",HNC17G:"OBF - Office of Budget & Finance",HNC17F:"DCCPS - Admin Resource Ctr - Div of Cancer Control & Poplulation Science",HNC17C:"OGA - Office of Grants Administration",HNC17L:"DCP - Admin Resource Ctr - Division of Cancer Prevention",HNC17N:"OWPD - Office of Workforce Planning & Development",HNC17K:"OM - Admin Resource Ctr - Office of Management",HNC17U:"DCEG - Admin Resource Ctr - Division of Cancer Epidemiology & Genetics",HNC17T:"CCR - Admin Resource Ctr - Center For Cancer Research",HNC17W:"ITRC - Information Technology Resource Center",HNC17V:"DCTD - Admin Resource Ctr - Division of Cancer Treatment & Diagnosis",HNC17Q:"EO - Ethics Office",HNC17P:"TTC - Technology Transfer Center",HNC17S:"OD - Admin Resource Ctr - Office of the Director",HNC17Y:"OWR - Office of Workforce Relations",HNC17X:"OWM - Office of Workforce Management",HNC177:"OMPC - Office of Management Policy & Compliance",HNC178:"OSFM - Office of Space & Facility Management",HNC17B:"OA - Office of Acquisitions",HNC17M:"DEA - Admin Resource Ctr - Div of Cancer Biolgy & Div of Extrmrl Activ", "Other" : "Other"};

/*
http://localhost:8080/cct/fyi-colloquium-2019/abstract-submissions-csv?webform_submission_value_1=Molecular%20and%20Cellular%20Biology%20and%20Microbiology&_format=csv
*/
  $.setupAbstractCategoryChangeEventListener = function() {
     var events = $._data(document.getElementById('edit-webform-submission-value-1'), "events");
     var hasEvents = (events != null);
     //console.log(hasEvents);
     if(!hasEvents) {
     	$("#edit-webform-submission-value-1").change(function(){
	      var filter = $('#edit-webform-submission-value-1').val();
      	  conosole.log("Abstract Change: "+filter);
      	  conssole.log("URL: http://localhost:8080/cct/fyi-colloquium-2019/abstract-submissions-csv?webform_submission_value_1=Molecular%20and%20Cellular%20Biology%20and%20Microbiology&_format=csv");
        });
     }

  }

  $.setupDivisionChangeEventListener = function() {
  	//console.log("Check to see if listener is there");
  	
     var events = $._data(document.getElementById('edit-division-office-center-'), "events");
     var hasEvents = (events != null);
     console.log(hasEvents);
     if(!hasEvents) {
     	//console.log("Adding Division EVENT Listener");
     	$("#edit-division-office-center-").change(function(){
	      //console.log("The DIVISON has been changed. "+$('#edit-division-office-center-').val());
	      var sac = $('#edit-division-office-center-').val();
	      
	      $.resetBranchOptions();
	      $.setBranchOptions(sac);
	      
	      //console.log("Branches: " + $.countBranches(sac));
	      // unhideBranches
	      //var count = $.countBranches(sac);
          //if(count == 0) {
	      	//$('#edit-branch-lab').val("NA");
	      	//$('.form-item-branch-lab').hide();
	      	//$.hideAllBranchesExceptNA();
	      	//$('#edit-branch-lab option[value=Other]').prop('selected', true);
	      //} else {
	      	//$('#edit-branch-lab').val("");
	      	//$.resetBranchOptions();
	      	//$.setBranchOptions(sac);
	      	//$('.form-item-branch-lab').show();
	      //}
	      //If no branches... Mark it Other and 
/*
	      if($('#edit-organization-type-select').val() == "HNC") {
	        console.log("You selected NCI.  Hurry.");
	        $.getColloquiumDOCList("HNC");
	        //Make a Rest service pass through on the server side.
	        //alert("We have the NCI - National Cancer Institute");
	      }
*/

	    });
     }

  }

  $.resetBranchOptions = function() {
	$("#edit-branch-lab option").each(function() {
	    //console.log(this.text + ' ' + this.value);
	    //console.log(this.value);
	    //this.prop('hidden', false);
	    $('#edit-branch-lab option[value="' + this.value + '"]').removeAttr('hidden');
	    $('#edit-branch-lab option[value="' + this.value + '"]').removeAttr('disabled');
        //$('#edit-branch-lab').val('');

	});
  }

  $.setBranchOptions = function(sac) {
	var branches = branches_json;
	//console.log("Count Branches for:" + sac);
	var lenOfSACName = sac.length;
	//console.log("lenOfSACName:" + lenOfSACName);
	var count = 0;

	$("#edit-branch-lab option").each(function() {

		//if( sac == "HNC7" || sac == "HNC9"){
			
			if(sac == this.value.substr(0, lenOfSACName) && this.value.length  >= (sac.length +1)) {
				//console.log(this.value + "Remove Hidden");
			    $('#edit-branch-lab option[value="' + this.value + '"]').removeProp('hidden');
			    $('#edit-branch-lab option[value="' + this.value + '"]').removeProp('disabled');
				count++;
			} else {
			    $('#edit-branch-lab option[value="' + this.value + '"]').attr('hidden', true);
			    $('#edit-branch-lab option[value="' + this.value + '"]').attr('disabled', true);
			}
			/*
		} else {

			if (sac == this.value.substr(0, lenOfSACName) && this.value.length  == (sac.length +1)) {
				//console.log(this.value + "Remove Hidden");
			    $('#edit-branch-lab option[value="' + this.value + '"]').removeProp('hidden');
			    $('#edit-branch-lab option[value="' + this.value + '"]').removeProp('disabled');
				count++;
			} else {
			    $('#edit-branch-lab option[value="' + this.value + '"]').attr('hidden', true);
			    $('#edit-branch-lab option[value="' + this.value + '"]').attr('disabled', true);

	   		}
	   	}
	   	*/
		if( sac == ""){
		    $('#edit-branch-lab option[value="' + this.value + '"]').attr('hidden', true);
		    $('#edit-branch-lab option[value="' + this.value + '"]').attr('disabled', true);
		}
	});
	
	//console.log("Total Selection: " + count);
	//Reset '- Select - ' option back which has a value of "".

    $('#edit-branch-lab option[value=""]').removeProp('hidden');
    $('#edit-branch-lab option[value=""]').removeProp('disabled');
    $('#edit-branch-lab').val('');

	return count;

  }

  $.countBranches = function(sac) {
		var branches = branches_json;
		//console.log("Count Branches for:" + sac);
		var lenOfSACName = sac.length;
		var count = 0;

		for (var key in branches) {
	    	var value = branches[key];
	    	//console.log("key:"+key +" value:"+value);
			if(sac == key.substr(0, lenOfSACName) && key.length  == (sac.length +1) && sac != "HNC7") {
				count++;
			}
			if(sac == key.substr(0, lenOfSACName) && key.length  >= (sac.length +1) && sac == "HNC7") {
				count++;
			}
		}

		//console.log("count: " + count);
		return count;
	}

  /*

  Step 1: get Org by Short name #organization-type-select
      a) Convert to SAC code
      b) Get list of children of that SAC code. (i.e. NCI has a sac code of HNC)
      c) Query https://userinfo-dev.nci.nih.gov/api/org/sac/HNC and get sac code of HNC.
      d) Query https://userinfo-dev.nci.nih.gov/api/org/subbranches/sac/hnc to get a list of Divisions (DOCs).
  */

/*

  $.setupColloquiumListeners = function() {
  	//var events = $._data($("#dit-organization-type-select")[0], "events")
  	//console.dir(events);
    console.log("add listeners");
    $("#edit-organization-type-select").change(function(){
      console.log("The text has been changed. "+$('#edit-organization-type-select').val());
      if($('#edit-organization-type-select').val() == "HNC") {
        console.log("You selected NCI.  Hurry.");
        $.getColloquiumDOCList("HNC");
        //Make a Rest service pass through on the server side.
        //alert("We have the NCI - National Cancer Institute");
      }
    }); 
  }

  $.getColloquiumDOCList = function(sac) {
    //Look up SAC code by org name
    console.log("Looking up subbranches");
    sac = "HNC1";
    var query = userinfo_server + 'subbranches/sac/' + sac;
    console.log('query: '+query);
    $.ajax({
      url: query
    })
      .done(function( data ) {
      	$.getBranches();
        $.populateColloquiumDOCList(data);
        $('#edit-division-office-center-select').prop('data-populated', 'true');
        if ( console && console.log ) {
          console.log( "Sample of data:", data);
          console.dir(data[0]);
        }
    });
  }

  $.getBranches = function() {
  	console.log("Get HNC7");
	var divsion_sacs = ["HNC7", "HNCC", "HNCD", "HNC9", "HNC4", "HNCB", "HNC5", "HNC1", "HNC1D", "HNC1P", "HNC1Q", "HNC1N", "HNC1E", "HNC1R", "HNC1L", "HNC1H", "HNC1M", "HNC14", "HNC1K", "HNC17", "HNC16", "HNC1J"];
    sac = "HNC1";
    $.each(divsion_sacs, function() {

    var query = userinfo_server + 'subbranches/sac/' + this;
    console.log('query: '+query);
    $.ajax({
      url: query
    })
      .done(function( data ) {

	    console.dir(data);
	    var foo;
	    $.each(data, function() {
	      //console.dir(this);
	      //$dropdown.append($("<option />").val(this.sac).text(this.shortName + ' - ' +$.titleCase(this.name)));
	      console.info("          "+"'"+this.sac+"': '"+this.shortName + " - " +$.titleCase(this.name)+"'");
	      foo += "          "+"'"+this.sac+"': '"+this.shortName + " - " +$.titleCase(this.name)+"'\n";
	      //console.info("          "+"'"+this.sac);
	    });
	    console.log("Branches:");
	    console.info(foo);

    });
  });

  } 

  $.populateColloquiumDOCList =function(data) {
    var $dropdown = $("#edit-division-office-center-select");
    $dropdown.empty();
    $dropdown.append($("<option />").val("").text("- Select -"));
    //<option value="NCI/CCR">NCI/CCR</option><option value="" selected="selected">- Select -</option><option value="NCI/DCEG">NCI/DCEG</option><option value="Leidos">Leidos</option><option value="_other_">Other…</option>
    console.dir(data);
    var foo;
    $.each(data, function() {
      //console.dir(this);
      $dropdown.append($("<option />").val(this.sac).text(this.shortName + ' - ' +$.titleCase(this.name)));
      console.info("          "+"'"+this.sac+"': '"+this.shortName + " - " +$.titleCase(this.name)+"'");
      foo += "          "+"'"+this.sac+"': '"+this.shortName + " - " +$.titleCase(this.name)+"'\n";
      //console.info("          "+"'"+this.sac);
    });
    console.log("FOO Baby");
    console.info(foo);
    $dropdown.append($("<option />").val("_other_").text("Other…"));
  }
  */
  
  $.titleCase = function(str) {
    str = str.toLowerCase().split(' ');
    for (var i = 0; i < str.length; i++) {
      if(str[i] == 'of' || str[i] == 'the' || str[i] == 'and' || str[i] == 'for') {
        str[i] = str[i].charAt(0).toLowerCase() + str[i].slice(1); 
      } else {
        str[i] = str[i].charAt(0).toUpperCase() + str[i].slice(1); 
      }
    }
    return str.join(' ');
  }

  $.urlParam = function(name){
    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
    var return_val;
    if(results == null) {
      return_val = "";
    } else {
      return_val = results[1];
    }
    return return_val;
  }

})(window.jQuery);