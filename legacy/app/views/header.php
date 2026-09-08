<?PHP
    //print generateHtml('gui/normalHeader', array());
    //print generateHTML('gui/normalTop', array());

    include( ROOT .'/app/templates/gui/normalHeader.php');
    include( ROOT .'/app/templates/gui/normalTop.php');

    ?>

    <div class="container-fluid">

        <div class="row-fluid">
            <div class="span2">

                <ul class="nav nav-list well">


                    <auth:bulkUploader>
                    <li class="nav-header"><i class="icon-cloud-download"></i> Klant import </li>

                    <li><a href="{LB}/sampleBuffers/bulkUploader" tabindex="-1" > Aanmelden - importeren  </a>
                    </li>

                    </auth>


                    <li class="nav-header"><i class="icon-beaker"></i> {MESA_LSB_SAMPLES_TITLE}</li>

                    <auth:preportal>
                        <li><a href="{LB}/sampleBuffers/portal" tabindex="-1" > Voorportaal <span id="buffer_size" class="badge badge-info pull-right"></span></a> </li>
                    </auth>

                    <auth:registerSampleSidebarLink>
                      <li><a href="{LB}/samples/add" tabindex="-1" > {MESA_LSB_SAMPLES_REGISTER} </a> </li>
                    </auth>

                    <auth:registerSampleLegionellaSidebarLink>
                      <li><a href="{LB}/samples/addleg" tabindex="-1" > Aanmelden legionella </a> </li>
                    </auth>

                    <auth:registerSampleRodacSidebarLink>
                    <li><a href="{LB}/samples/addRodac" tabindex="-1" > Aanmelden RODAC-afdruk </a> </li>
                    </auth>

                    <auth:registerSampleEmptySidebarLink>
                    <li><a href="{LB}/samples/update" tabindex="-1" > Analyses toevoegen <span id="empty_samples_badge" class="badge badge-info pull-right"></span></a> </li>
                    </auth>

                    <auth:sampleLookupSidebarLink>
                      <li><a href="{LB}/samples/lookup" tabindex="-1" > Resultaten invoeren </a> </li>
                      <li><a href="{LB}/samples/runningConf" tabindex="-1" >Lopende bevestigingen </a> </li>
                   </auth>

                   <auth:thtportal>
                   <li><a href="{LB}/sampleBuffers/stagedTht" tabindex="-1" >THT onderzoeken
                      <span id="tht_waiting_badge" class="badge badge-warning pull-right"></span>
                   </a> </li>
                   </auth>

                    <auth:sampleListSidebarLink>
                        <li class="flex justify-between items-center"><a href="{LB}/samples/register" style="flex-grow: 1" tabindex="-1" > Monster lijst</a> <div id="non_innoced_samples" onClick="openInnocOpenModal();" class="badge badge-important pull-right"></div> </li>
                    </auth>

                    <auth:fotoMenuItem>
                    <li class="flex justify-between items-center"><a href="{LB}/samples/scanAndSnap" style="flex-grow: 1" tabindex="-1" > Monster foto's</a>  </li>
                    </auth>

                    <li class="nav-header"><i class="icon-suitcase"></i> {MESA_LSB_PROJECTS_TITLE}</li>

                    <auth:projectLookupSidebarLink>
                    <li><a href="{LB}/projects/search" tabindex="-1" > {MESA_LSB_PROJECTS_LOOKUP} </a> </li>

                    <li><a href="{LB}/projects/viewPending" tabindex="-1" > Ontvangen <span id="pending_research_badge" class="badge badge-info pull-right"></span></a> </li>
                    <li><a href="{LB}/projects/viewRunning" tabindex="-1" > Lopend <span id="open_research_badge" class="badge badge-info pull-right"></span></a> </li>
                    <li><a href="{LB}/projects/viewCompleted" tabindex="-1" > Afgerond <span id="auth_waiting_badge" class="badge badge-info pull-right"></span></a> </li>
                    <li><a href="{LB}/projects/viewAuthorised" tabindex="-1" > Geautoriseerd <span id="rap_waiting_badge" class="badge badge-info pull-right"></span></a> </li>
                    <li><a href="{LB}/projects/viewReported" tabindex="-1" > PDF gegenereerd</a> </li>
                    <li><a href="{LB}/projects/viewBlocked" tabindex="-1" > Geblokkeerd </a> </li>
                    <li><a href="{LB}/billing/scan2bill" tabindex="-1" > Facturatie </a> </li>


                    <!-- <li><a href="{LB}/projects/recent" tabindex="-1" > {MESA_LSB_PROJECTS_RECENT} </a> </li> -->
                    <!-- <li><a href="{LB}/projects/overview" tabindex="-1" > {MESA_LSB_PROJECTS_OVERVIEW} </a> </li> -->
                    </auth>


                    <li class="nav-header"><i class="icon-shield"></i> Borging </li>
                    <auth:assuranceFormSidebarLink>
                      <li><a href="{LB}/assuranceForms/view" tabindex="-1" > Borgingsformulier <span id="assurance_waiting_badge" class="badge badge-warning pull-right"></span></a></li>
                      <li><a href="{LB}/samples/auditTrail" tabindex="-1" > AuditTrail monsters </a> </li>
                    </auth>

                    <li class="nav-header"><i class="icon-group"></i> {MESA_LSB_CLIENTS_TITLE}</li>
                    <auth:clientDashboardButton>
                      <li><a href="{LB}/clients/search" tabindex="-1" > {MESA_LSB_CLIENTS_MANAGE} </a> </li>  
                    </auth>

                    <auth:clientcats>
                      <li><a href="{LB}/clientCategories/list" tabindex="-1" > Klant categorieën </a> </li>
                    </auth>

                    

                    <li class="nav-header"><i class="icon-barcode"></i> {MESA_S2P_MAINTTILE}</li>

                    <auth:scan2printButtons>
                        <li><a href="{LB}/printing/s2p" tabindex="-1" > {MESA_S2P_S2P} </a> </li>
                    </auth>

                    <auth:listsButtonLink>
                    <li><a href="{LB}/printing/lists" tabindex="-1" > {MESA_S2P_LISTS} </a> </li>
                    </auth>

                    <auth:dataMining>                    
                    <li class="nav-header"><i class="icon-magnet"></i> Data-mining</li>
                  
                      <li><a href="{LB}/dataMining/export" tabindex="-1" > Resultaten uitvoeren </a> </li>
                      <li><a href="{LB}/exports/search" tabindex="-1" > Rapportages </a> </li>
                    </auth>
                    
                    <auth:deauthList>
                      <li><a href="{LB}/dataMining/deauth" tabindex="-1" > Deauthorisatie  </a> </li>
                    </auth>       
                </ul>
            </div>
