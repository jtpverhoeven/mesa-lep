<div class="container-fluid">

    <div class="row-fluid">
        <div class="span2">

            <ul class="nav nav-list well">

                    <li class="nav-header"><i class="icon-building"></i> Client Portal</li>

                    <li><a href="{LB}/portalAssays/listing" tabindex="-1" > Beschikbare analyses</a> </li>
                    <li><a href="{LB}/portal/connection" tabindex="-1" > Connectie </a> </li>
                    <li><a href="{LB}/emailTemplates/index" tabindex="-1" >  Uitgaande email </a></li>


                    <li class="nav-header"><i class="icon-print"></i> Labels </li>

                     <auth:labelDesignButtons>
                        <li><a href="{LB}/labelDesigns/listing" tabindex="-1" > {MESA_LBD_LISTING}</a> </li>
                     </auth>

                    <auth:labelEventButtons>
                        <li><a href="{LB}/labelEvents/listing" tabindex="-1" > {MESA_LBD_LABELPRINTEVENTS}</a> </li>
                        <li><a href="{LB}/printing/profiles" tabindex="-1" > Print profielen</a> </li>
                    </auth>

                    <li class="nav-header"><i class="icon-exchange"></i> {MESA_ASB_RESEARCHFLOW}</li>

                    <auth:researchProfilesSidebarLink>
                    <li><a href="{LB}/researchProfiles/listing" tabindex="-1" > {MESA_ASB_RESPROFILES} </a> </li>
                    </auth>

                    <auth:assaysSidebarLink>
                    <li><a href="{LB}/assays/listing" tabindex="-1" > {MESA_ASB_ASSAYS} </a> </li>
                    </auth>

                    <auth:assayTypeSidebarLink>
                    <li><a href="{LB}/assayTypes/listing" tabindex="-1" > {MESA_ASB_ASSAYTYPES} </a> </li>
                    </auth>

                    <auth:samplingProceduresSidebarLink>
                    <li><a href="{LB}/sampleProcedures/listing" tabindex="-1" > {MESA_ASB_SAMPLEPROCEDURES} </a> </li>
                    </auth>
                    

                    <li class="nav-header"><i class="icon-circle"></i> {MESA_ASB_BASICSETTINGS}</li>

                    <auth:worklistadmin>
                    <li><a href="{LB}/workLists/show" tabindex="-1" > Werklijsten </a> </li>
                    </auth>

                    <auth:projectFieldsSidebarLink>
                    <li><a href="{LB}/projectFields/listing" tabindex="-1" > {MESA_ASB_PROJECTFIELDS} </a> </li>
                    </auth>

                    <auth:assayFieldsSidebarLink>
                    <li><a href="{LB}/assayFields/listing" tabindex="-1" > {MESA_ASB_GLOBALASSAYFIELDS} </a> </li>
                    </auth>

                    <auth:sampleFieldsSidebarLink>
                    <li><a href="{LB}/sampleFields/listing" tabindex="-1" > {MESA_ASB_SAMPLEFIELDS} </a> </li>
                    </auth>

                    <auth:sampleProcedureFieldsSidebarLink>
                    <li><a href="{LB}/sampleProcedureFields/listing" tabindex="-1" >  {MESA_ASB_SAMPLINGFIELDS} </a> </li>
                    </auth>

                    <auth:mediaListing>
                      <li><a href="{LB}/media/listing" tabindex="-1" >  Media &amp; Bevestigingen </a> </li>
                      <li><a href="{LB}/confirmationTables/listing" tabindex="-1" >  Bevestigings tabellen </a> </li>
                    </auth>

                    <li><a href="{LB}/matrix/index" tabindex="-1" >  Analyse matrices </a> </li>

                    <li><a href="{LB}/referenceSources/index" tabindex="-1" > Referentie bronnen </a> </li>

                    <li><a href="{LB}/footers/index" tabindex="-1" > Rapportage voetteksten </a> </li>

                    <li class="nav-header"><i class="icon-user"></i> {MESA_ASB_USERSANDGROUPS} </li>

                    <auth:userAdminSidebarLink>
                    <li><a href="{LB}/userAdmin/dashboard" tabindex="-1" > {MESA_ASB_MANAGEUSERS} </a> </li>
                    </auth>

                    <auth:groupAdminSidebarLink>
                    <li><a href="{LB}/userGroups/listing" tabindex="-1" > {MESA_ASB_USERGROUPS} </a> </li>
                    </auth>
                        <!--
                    <?PHP
                      if(!isset($_SESSION['DEVELOPMENT']) || $_SESSION['DEVELOPMENT'] == False){
                    ?>
                     <li class="nav-header"><i class="icon-cloud-download"></i> {MESA_ASB_AUDITASSIST} </li>


                    <auth:auditAssistSyncButton>
                    <li><a href="{LB}/sync/syncTool" tabindex="-1" > {MESA_ASB_SYNCAUDITASSIST} </a> </li>
                    </auth>
                    <?PHP
                      }
                    ?>

                    -->

                    <li class="nav-header"><i class="icon-check"></i> Testing </li>
                    
                    <li><a href="{LB}/testSets/listing" tabindex="-1" > Tests uitvoeren </a> </li>                    


                    <li class="nav-header"><i class="icon-cog"></i> {MESA_ASB_SYSTEMMANAGEMENT}</li>


                        <auth:logbookButtons>
                            <li><a href="{LB}/logs/listing" tabindex="-1" >  {MESA_ASB_VIEWLOGFILES} </a> </li>
                        </auth>

                        <auth:cvarButtons>
                            <li><a href="{LB}/cvars/listing" tabindex="-1" > {MESA_ASB_ADVANCED} </a> </li>
                            <li><a href="{LB}/admin/calculations" tabindex="-1" > Berekeningen </a> </li>
                        </auth>

                    <?PHP
                      if(isset($_SESSION['DEVELOPMENT']) && $_SESSION['DEVELOPMENT'] == True){
                    ?>
                    <li><a href="{LB}/landings/pushDevToProduction" tabindex="-1" > Ontwikkel database overzetten </a> </li>
                    <?PHP
                      }
                    ?>


            </ul>
        </div>
