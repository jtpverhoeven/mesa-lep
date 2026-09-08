<div class="span10">         

    <div class="row-fluid">        
          <div class="span3">
              <div class="well well-small">
                    <h6><i class="icon-building"></i> Find by client</h6>
                    <input id="client_name" type="text" class="ajax-typeahead input-block-level" autocomplete="off" placeholder="Type client name" />
                    <input id="client" type="hidden" />
              </div>
                            
              <div id="recentDiv" class="well well-small">
                    <h6><i class="icon-time"></i> Recent projects</h6>
                    
                    <div class="tabbable tabs-below">
                    <div class="tab-content">
                       <div class="tab-pane active" id="0day">
                           
                           <div id="0dayScroll">                                                      
                                <div class="list-group" id="todayList">
                                   {projects_today}
                                </div>                               
                           </div>
                           
                       </div>
                       <div class="tab-pane" id="1day">
                           
                            <div id="1dayScroll">      
                                <div class="list-group" id="yesterdayList">
                                   {project_1day}
                                </div>     
                            </div>
                           
                       </div>        
                       <div class="tab-pane" id="older">
                           
                           <div id="olderScroll">
                                <div class="list-group" id="lastweekList">
                                   {project_lastweek}
                                </div>  
                           </div>
                           
                       </div>
                      
                      
                    </div>
                    <ul class="nav nav-tabs">
                      <li class="active"><a href="#0day" data-toggle="tab">Today</a></li>
                      <li class=""><a href="#1day" data-toggle="tab">Yesterday</a></li>                     
                      <li class=""><a href="#older" data-toggle="tab">&LT; Week</a></li>
                      
                      
                    </ul>
                  </div>
               </div>
                            
          </div>
        
        <div  class="span4">
             <div id="customerProjectsDiv" class="well well-small">
                    <h6><i class="icon-suitcase"></i> Client projects <i id="custNameDisplay"></i> </h6>
                   
                    <div id="customerProjects" class="list-group">
                  </div>
              </div>
        </div>  
        
        <div  class="span4">
             <div id="samplesDiv" class="well well-small">
                    <h6><i class="icon-beaker"></i> Samples in project</h6>
                    
                               
                    <ul id="samplesInProject" class="list-group">
                       
                    </ul>
                    
              </div>
            
            
        </div>
        
        
        <div id="projectToolbar" class="span1 hide">                
            <div class="well well-small">
            
            <p><button id="followProjectButton" class="btn input-block-level" title="Follow project"><i class="icon-bookmark-empty"></i></button> </p>            
            <p><button id="unFollowProjectButton" class="btn input-block-level" title="Following, click to unfollow"><i class="icon-bookmark bookmark-red"></i></button> </p>                        
            
            
            <p><button id="showResultListButton"class="btn input-block-level" title="Show Result list"><i class="icon-list"></i></button> </p>

            <p><button id="authoriseAllSamplesButton" class="btn input-block-level" title="Authorise all samples"><i class="icon-star "></i></button> </p>
            <p><button id="deAuthoriseAllSamplesButton" class="btn input-block-level" title="Deauthorise all samples"><i class="icon-star-empty"></i></button> </p>



            <p><button id="authoriseProjectButton" class="btn input-block-level" title="Authorise project"><i class="icon-ok"></i></button> </p>
            <p><button id="deAuthoriseProjectButton" class="btn input-block-level" title="Deauthorise project"><i class="icon-remove"></i></button> </p>
            
            
            <p><button id="removeProjectButton" class="btn btn-warning input-block-level" title="Remove project and samples"><i class="icon-trash"></i></button> </p>
            
            
            </div>
            
            <div class="well well-small">
            <p><button class="btn input-block-level" title="Show project page"><i class="icon-group"></i></button> </p>            
            <p><button class="btn input-block-level" title="Show customer"><i class="icon-building"></i></button> </p>            
            <p><button class="btn input-block-level" title="Generate report"><i class="icon-paste"></i></button> </p>            
            <p><button class="btn input-block-level" title="Customer download"><i class="icon-cloud-download"></i></button> </p>            
            
            
            </div>
        </div>
        
        
    </div>
    
</div>

<div class="hidden">    
    <input type="hidden" id="entryProject" value="{entry_project}" />
    <input type="hidden" id="entryClient" value="{entry_client}" />
    <input type="hidden" id="entryClientName" value="{entry_client_name}" />
</div>


<script>
    
selected_client = false;
selected_project = false;  
project_authorised = false;
    
$(function() {
    
     $('#0dayScroll').slimScroll({ height: '250px' });
     $('#1dayScroll').slimScroll({ height: '250px' });
     $('#olderScroll').slimScroll({ height: '250px' });
   
     //focus on customer field        
   $('#client_name').focus();
   
   entryScan();
    
   $("#client_name").bind('keydown', 'return', function(){
      
   });    

    $("#client_name").typeahead({
            minLength: 2,
            source: function(query, process) {

                var data;
                clients = [];
                map = {};

                $.ajax({
                    type: "POST",
                    async: false,
                    data: {query: query},
                    dataType: 'json',
                    url: "{LB}/clients/predict"
                }).done(function(msg) {
                    data = msg;
                });

                $.each(data, function(i, client) {
                    map[client[i].name] = client[i];
                    clients.push(client[i].name);
                });

                process(clients);
            }
        }).blur(function() {

            if ($(this).val() == '') {
                $('#client').val('NULL');
                $(this).val('');                
                selected_client = null;                
                return;
            }

            if ($.inArray($(this).val(), clients) === -1) {
                alert('Invalid customer');
                $('#client').val('NULL');
                $(this).val('');                
                selected_client = null;
            } else {
                clientId = map[$(this).val()].id;
                selected_client = clientId;                
                $('#client').val(clientId);           
                loadCustomerProjects(clientId);
            }
        });
        
         $("#recentDiv").find('.list-group-item').click(function(){
             
             $("#recentDiv").find('.list-group-item').removeClass('active');
             $(this).addClass('active');
             loadCustomerProjects($(this).attr('clientId'), $(this).attr('projectId'));
             loadProject($(this).attr('projectId'));             
         });      
         
         $("#customerProjects").on('click', '.list-group-item', function(){
              $("#customerProjects").find('.list-group-item').removeClass('active');  
              $(this).addClass('active');
              loadProject($(this).attr('projectId'));
         });        
         
         $('#showResultListButton').click(function(){             
             popUp('{LB}/projects/generateResultList/' + selected_project, selected_project , '920', '600')
         });
         
         $('#authoriseAllSamplesButton').click(function(){             
             authoriseAllSamples(selected_project); 
         });
         
         $('#deAuthoriseAllSamplesButton').click(function(){             
            deAuthoriseAllSamples(selected_project); 
         });            
         
         $('#authoriseProjectButton').click(function(){                  
                bootbox.confirm("<h3>{MESA_CONFIRM_PROJ_AUTH_TITLE}</h3> <p>{MESA_CONFIRM_PROJ_AUTH_MSG}</p>", function(result) {             
                    if(result == true){
                        authoriseProject(selected_project);
                    }
                });                     
         });         
         
        $('#deAuthoriseProjectButton').click(function(){             
            bootbox.confirm("<h3>{MESA_CONFIRM_PROJ_DEAUTH_TITLE}</h3> <p>{MESA_CONFIRM_PROJ_DEAUTH_MSG}</p>", function(result) {             
                if(result == true){
                   deAuthoriseProject(selected_project);
                }
            });                       
         });         
         
         $('#removeProjectButton').click(function(){
             
             var random_check = Math.floor(Math.random()*(200-100+1)+100);
             
             bootbox.prompt("<h3>{MESA_CONFIRM_PROJ_DELETE_TITLE}</h3> <p>{MESA_CONFIRM_PROJ_DELETE_MSG}</p> <p> Security code <span class='label label-warning'>" + random_check +"</span>", function(result) {             
                if(result != null){
                   if(result == random_check){
                        $.ajax({
                            type: "POST",                            
                            url: "{LB}/projects/removeProject/" + selected_project
                        }).done(function(msg) {            
                            $('#samplesInProject').html('');
                            loadCustomerProjects(selected_client);
                            updateRecent();
                        }); 
                   } else{
                       bootbox.alert("<h3>{MESA_ERROR_PROJ_DELETE_TITLE}</h3> <p> {MESA_ERROR_PROJ_DELETE_MSG} </p> ");
                   }
                }
            });                       
         });
         
});

function loadCustomerProjects(customerId, loadedProject){

    selected_client = customerId;

     $.ajax({
        type: "POST",   
        dataType: 'json',
        url: "{LB}/projects/clientProjectsList/" + customerId
    }).done(function(msg) {
        
         $('#customerProjects').html(msg['panel']);
         $('#custNameDisplay').html(msg['client_name']);
         
         
         if (loadedProject !== 'undefined') {             
             $('#clientproject_' + loadedProject).addClass('active');
         }
         
         $('#customerProjectsDiv').highLight();
    });   
}

function loadProject(projectId){
    $.ajax({
        type: "POST",                
        url: "{LB}/projects/loadProjectSamples/" + projectId
    }).done(function(msg) {        
         $('#samplesInProject').html(msg);               
         $('#samplesDiv').highLight();
         selected_project = projectId;         
         updateToolBar(projectId);
         $('#projectToolbar').show();
    });         
}

function updateToolBar(projectId){
    $.ajax({
        type: "POST",                
        dataType: "json",
        url: "{LB}/projects/toolbarUpdate/" + projectId
    }).done(function(res) {        
         console.log(res);
         
        if(res['following_project'] == false){
             $('#followProjectButton').show();
             $('#unFollowProjectButton').hide();
        } else {
             $('#followProjectButton').hide();
             $('#unFollowProjectButton').show();
        }
        
        if(res['project_auth'] == 'show_deauth'){
            $('#deAuthoriseAllSamplesButton').addClass('disabled');
            project_authorised = true;
            $('#deAuthoriseProjectButton').show();
            $('#authoriseProjectButton').hide();
        } 
        
        if(res['project_auth'] == 'show_auth'){       
            $('#deAuthoriseAllSamplesButton').removeClass('disabled');
            project_authorised = false;
            $('#deAuthoriseProjectButton').hide();
            $('#authoriseProjectButton').show();
        }
    
        if(res['auth_button'] == 'show_deauth'){
             $('#authoriseAllSamplesButton').hide();
             $('#deAuthoriseAllSamplesButton').show();
         } else{
             $('#authoriseAllSamplesButton').show();
             $('#deAuthoriseAllSamplesButton').hide();
         }
         
    }); 
}

function authoriseProject(projectId){
    $.ajax({
        type: "POST",        
        url: "{LB}/projects/authoriseProject/" + projectId
    }).done(function(msg) {          
        loadCustomerProjects(selected_client, selected_project);
        loadProject(selected_project);
        updateToolBar(projectId);
    });         
}

function deAuthoriseProject(projectId){
    $.ajax({
        type: "POST",        
        url: "{LB}/projects/deauthoriseProject/" + projectId
    }).done(function(msg) {         
        loadCustomerProjects(selected_client, selected_project);
        loadProject(selected_project);
        updateToolBar(projectId);
    });         
}

function authoriseProjectSample(sampleId, projectId){

    if(project_authorised == true){
        bootbox.alert("<h3>{MESA_AUTH_ERROR_LOCKED_TITLE}</h3> <p> {MESA_AUTH_ERROR_LOCKED_MSG} </p> ");
        return false;
    }

    $.ajax({
           type: "POST",        
           data: { sampleId: sampleId},
           url: "{LB}/sampleAnalysis/authoriseAll"
       }).done(function(msg) {            
            loadProject(projectId);
       }); 
}

function deauthoriseProjectSample(sampleId, projectId){
    
    if(project_authorised == true){
        bootbox.alert("<h3>{MESA_AUTH_ERROR_LOCKED_TITLE}</h3> <p> {MESA_AUTH_ERROR_LOCKED_MSG} </p> ");
        return false;
    }

    $.ajax({
           type: "POST",        
           url: "{LB}/sampleAnalysis/authoriseAll/" + sampleId + "/" + 1
       }).done(function(msg) {            
            loadProject(projectId);
       }); 
}



function authoriseAllSamples(projectId){

    if(project_authorised == true){
        bootbox.alert("<h3>{MESA_AUTH_ERROR_LOCKED_TITLE}</h3> <p> {MESA_AUTH_ERROR_LOCKED_MSG} </p> ");
        return false;
    }

    $.ajax({
           type: "POST",        
           data: { projectId: projectId},
           url: "{LB}/samples/authoriseAllInProject"
       }).done(function(msg) {            
            loadProject(projectId);
       }); 
}

function deAuthoriseAllSamples(projectId){

    if(project_authorised == true){
        bootbox.alert("<h3>{MESA_AUTH_ERROR_LOCKED_TITLE}</h3> <p> {MESA_AUTH_ERROR_LOCKED_MSG} </p> ");
        return false;
    }

    $.ajax({
           type: "POST",        
           data: { projectId: projectId},
           url: "{LB}/samples/deAuthoriseAllInProject"
       }).done(function(msg) {            
            loadProject(projectId);
       }); 
}




function entryScan(){
    
    var client =  $('#entryClient').val();
    var clientName = $('#entryClientName').val();
    var clientProject = $('#entryProject').val();
            
    
    if($('#entryClient').val() != ''){
        
        $('#client_name').val(clientName);
        
        if(clientProject != ''){
           selected_client = client;
           loadCustomerProjects(client, clientProject);
           loadProject(clientProject);
        }
        
        else{
            loadCustomerProjects(client);
        }

    }
}

function sampDetails(sampleId){        
}

function removeSample(sampleId){
       bootbox.confirm("<h3>{MESA_REMOVE_PROJ_SAMPLE_TITLE}</h3> <p>{MESA_REMOVE_PROJ_SAMPLE_MSG}</p>", function(result) {             
            if(result == true){
                 $.ajax({
                    type: "POST",                            
                    url: "{LB}/samples/removeSample/" + sampleId
                }).done(function(msg) {            
                     loadProject(selected_project);
                }); 
            }
        });       
}

function updateRecent(){
    $.ajax({
        type: "POST", 
        dataType: 'json',
        url: "{LB}/projects/recentProjectsReturn"
    }).done(function(msg) {            
         $('#todayList').html(msg['today']);
         $('#yesterdayList').html(msg['yesterday']);
         $('#lastweekList').html(msg['lastweek']);         
         $('#recentDiv').highLight();
    });         
}    
</script>