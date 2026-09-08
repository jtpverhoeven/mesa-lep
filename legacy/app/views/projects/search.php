<div class="span10">

  <div id="sampleLockDiv" class="alert alert-block hide">
    <img id="usageAvatar" class='pull-left img-polaroid avatar-32' style='margin-right: 15px;' src='' />
    <h4>{MESA_PLU_PROJECTLOCKED_TITLE} </h4>
    {MESA_PLU_PROJECTLOCKED_DESCRIPTION} <strong><span id="usageName"></span></strong> {MESA_PLU_PROJECTLOCKED_DESCRIPTION2} 
    <br />
    <button id="closeLockAlertBtn" class="btn btn-primary"> Sluiten </button>
  </div>

  <div id="blockByDiv" class="alert alert-info alert-block hide">    
    <img id="blockByAvatar" class='pull-left img-polaroid avatar-32' style='margin-right: 15px;' src='' />
    <h4>Project autorisatie tijdelijk geblokkeerd</h4>
   <strong><span id="blockByName"></span></strong> heeft dit project tijdelijk geblokkeerd voor autorisatie, met de reden: <span id="blockReason"></span>.
    <br />
    
  </div>

  <div id="projectPageShow" class="row-fluid">

    <div class="span4">
      <div class="well well-small">

        <div class="tabbable">
          <h6><i class="icon-search"></i> {MESA_PLU_LOOKUPTITLE} </h6>

          <div class="tab-content tabs-below">

            <div class="tab-pane" id="searchForBilling">

              <div class="control-group" id="project_search_billing_cg">
                <div class="controls">
                  <div class="input-prepend input-append input-block-level">
                    <span class="add-on"><i class="icon-eur"></i></span>
                    <input type="text" id="proj_search_billing" name="proj_search_billing" value=""  class="input-block-level" placeholder="Zoek op barcode voor facturatie gegevens" />
                  </div>
                </div>
              </div>
            
            </div>

            <div class="tab-pane " id="searchByClient">

              <input  id="client_name" type="text"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off"
                spellcheck="false"
                class="select2-input select2-default input-block-level"
                tabindex="0"
                placeholder=""
                aria-activedescendant="select2-result-label-351"
              />


              <input id="subclient" name="subclient" type="hidden" class="hide" />

              <select id="projectRange" name="projectRange" class="input-block-level">
                <option value="mostRecent">{MESA_PLU_MOSTRECENT}</option>
                <option value="all">{MESA_PLU_ALL}</option>
              </select>

              <button style="margin-top: 10px; margin-bottom: 10px;" id="searchByClientButton" type="button" class="btn btn-primary btn-mini"><i class="icon-search"></i> {MESA_PLU_SEARCH} </button>
              <input id="client" type="hidden" />
            </div>

            <div class="tab-pane" id="searchByReference">

              <div class="control-group" id="project_search_sample_cg">
                <div class="controls">
                  <div class="input-prepend input-append input-block-level">
                    <span class="add-on"><i class="icon-tags"></i></span>
                    <input type="text" id="proj_search_ref" name="proj_search_ref" value=""  class="input-block-level" placeholder="{MESA_PLU_BYREFERENCE}" />
                  </div>
                </div>
              </div>

              <button style="margin-top: 10px; margin-bottom: 10px;" id="searchByReferenceButton" type="button" class="btn btn-primary btn-mini"><i class="icon-search"></i> {MESA_PLU_SEARCH} </button>


            </div>

            <div class="tab-pane" id="searchByDate">

              <div class="control-group" id="project_search_begin_cg">
                <div class="controls">
                  <div class="input-prepend input-append input-block-level">
                    <span class="add-on">{MESA_PLU_FROM}</span>
                    <input type="text" id="proj_search_begin" name="proj_search_begin" value="{yesterday}"  class="input-block-level" placeholder="{MESA_PLU_FROM}" />
                  </div>
                </div>
              </div>

              <div class="control-group" id="project_search_begin_cg">
                <div class="controls">
                  <div class="input-prepend input-append input-block-level">
                    <span class="add-on">{MESA_PLU_TO}</span>
                    <input type="text" id="proj_search_end" name="proj_search_end" value="{yesterday}"  class="input-block-level" placeholder="{MESA_PLU_TO}" />
                  </div>
                </div>
              </div>

              <button style="margin-top: 10px; margin-bottom: 10px;" id="searchByDateButton" type="button" class="btn btn-primary btn-mini"><i class="icon-search"></i> {MESA_PLU_SEARCH} </button>

              <script>
              $(document).ready(function() {
                $('#proj_search_begin, #proj_search_end').Zebra_DatePicker({
                  format: 'd-m-Y',
                  zero_pad: true,
                  show_icon: false,
                  offset: [10, 200],
                  readonly_element: false
                });
              });
              </script>

            </div>

            <div class="tab-pane active" id="searchBySample">

              <div class="control-group" id="project_search_sample_cg">
                <div class="controls">
                  <div class="input-prepend input-append input-block-level">
                    <span class="add-on"><i class="icon-barcode"></i></span>
                    <input type="text" id="proj_search_sample" name="proj_search_sample" value=""  class="input-block-level" placeholder="{MESA_PLU_SCANBAR}" />
                  </div>
                </div>
              </div>

              <button style="margin-top: 10px; margin-bottom: 10px;" id="searchBySampleButton" type="button" class="btn btn-primary btn-mini"><i class="icon-search"></i> {MESA_PLU_SEARCH} </button>

            </div>



            <ul class="nav nav-tabs">
              <li class="active"><a href="#searchBySample" data-toggle="tab" tabindex="-1" ><i class="icon-beaker"></i></a></li>
              <li class=""><a href="#searchByClient" data-toggle="tab" tabindex="-1" ><i class="icon-building"></i></a></li>
              <li class=""><a href="#searchByReference" data-toggle="tab" tabindex="-1" ><i class="icon-tag"></i></a></li>
              <li class=""><a href="#searchByDate" data-toggle="tab" tabindex="-1" ><i class="icon-calendar-empty"></i></a></li>
              <li class=""><a href="#searchForBilling" data-toggle="tab" tabindex="-1" ><i class="icon-eur"></i></a></li>
            </ul>
          </div>
          <div id="wishAlertDiv" class="alert alert-info alert-block hide" onClick="openClientWishes()"><span><i class="icon icon-smile"></i> Eisen / bijzonderheden gevonden!</span></div>
        </div>
      </div>

      <div class="well well-small" id="projWell">

        <div id="projectSearchSpace">
          <h6><i class="icon-suitcase"></i> {MESA_PLU_FOUNDPROJECTS} </h6>
          <div id="customerProjects" class="list-group">
          </div>

        </div>


        <div id="selectedProjectSpace" class="hide">
          <h6><i class="icon-suitcase"></i> {MESA_PLU_PROJECT}
            <span class="pull-right">

              <auth:projRevision>
                <button id="projectRevisions" class="btn btn-mini" title="{MESA_PLU_PROJECTREVISIONS}"><i class="icon-rotate-left"></i></button>
                <button id="projectHardcopies" class="btn btn-mini" title="Rapportages"><i class="icon-external-link"></i></button>
              </auth>

              <button id="projectFollow" class="btn btn-mini" title="{MESA_PLU_BUTFOLLOW}"><i id="projectFollowIcon" class="icon-bookmark-empty icon-red"></i></button>
              <!-- <button id="openProjectPageButton" onClick="goToProjectPage();" class="btn btn-mini" title="{MESA_PLU_BUTPROJECTPAGE}"><i class="icon-comment-alt"></i></button> -->
              <auth:editProjectInformation>
              <button id="editProjectDetailsBtn"class="btn btn-primary btn-mini" title="{MESA_PLU_BUTEDIT}"><i class="icon-edit"></i></button>
            </auth>
          </span>
        </h6>
        <span id="selectedProjectTable"></span>
        <button onClick="backToSearch();" id="backToSearchButton" class="btn btn-primary btn-mini"><i class="icon-chevron-left"></i> {MESA_PLU_BACKTOSEARCH}</button>

      </div>


    </div>

  </div>

  <div class="span3">
    <div class="well well-small" id="middleWell">


      <div class="tabbable">
        <div class="tab-content tabs-below">

          <div class="tab-pane active" id="projectSamplesTab">
            <h6><i class="icon-beaker"></i> {MESA_PLU_PROJECTCONTENTS}
              <span class="pull-right">

                <div class="btn-group">
                  <a class="btn btn-mini dropdown-toggle" data-toggle="dropdown" href="#"  tabindex="-1">
                    <i class="icon-print"></i>
                  </a>
                  <ul class="dropdown-menu pull-right">
                    <!-- <li id="projectStickers"><a href="#" onClick=""><i class="icon-barcode"></i> {MESA_PLU_PRINTPROJSTICKERS}</a></li> -->
                    <li id="projectExport"><a href="#" onClick="exportProject();"><i class="icon-external-link"></i> Rapport genereren</a></li>
                    <li id="projectExport"><a href="#" onClick="prelimExportProject();"><i class="icon-external-link"></i> Voorlopig rapport genereren</a></li>
                    <li class="divider"></li>
                    <li id="projectExport"><a href="#" onClick="fileSender();"><i class="icon-envelope"></i> Monster bestanden mailen</a></li>
                    <li class="divider"></li>
                    <li id="projectExport"><a href="#" onClick="dataExportProject();"><i class="icon-cloud-download"></i> Data uitvoeren</a></li>
                    

                  </ul>
                </div>

                <div class="btn-group">
                  <a class="btn btn-mini dropdown-toggle" data-toggle="dropdown" href="#"  tabindex="-1">
                    <i class="icon-cogs"></i>
                  </a>
                  <ul class="dropdown-menu pull-right">
                    <auth:authoriseButtons>
                    <li id="projAuth" class=""><a href="#" onClick="authProject();"><i class="icon-thumbs-up"></i> {MESA_PLU_AUTHORISEPROJECT}</a></li>
                    <li id="projDeauth" class="hide"><a href="#" onClick="deauthProject();"><i class="icon-thumbs-down"></i> {MESA_PLU_DEAUTHORISEPROJECT}</a></li>
                 
                  <li class="divider"></li>
                    <li id="quietAuth" class=""><a href="#" onClick="silentAuth();"><i class="icon-cloud-upload"></i> Stille autorisatie </a></li>
                    <li id="syncForce" class=""><a href="#" onClick="syncPortal();"><i class="icon-cloud-upload"></i> Portal sync forceren </a></li>
                    <li id="removePortal" class=""><a href="#" onClick="retractPortal();"><i class="icon-fire-extinguisher"></i> Resultaten uit portal halen</a></li>
                    </auth>
                  <li class="divider"></li>
                  <li id="changeProjectClient" class="hide"><a href="#" ><i class="icon-building"></i> Klant wijzigen</a></li>
                  <li class="divider"></li>                 
                  <auth:projectRemoveButton>
                  <li id="projRemove"><a href="#" onClick="removeProject();"><i class="icon-trash"></i> {MESA_PLU_REMOVEPROJECT}</a></li>
                  <li class="divider"></li>                 
                  <li id="lockProject"><a href="#" onClick="lockProject()"><i class="icon-lock"></i> Project autorisatie blokkeren</a></li>
                  <li id="unlockProject"><a href="#" onClick="unlockProject()"><i class="icon-unlock"></i> Project autorisatie vrijgeven</a></li>
                  </auth>
              </ul>
            </div>
          </span></h6>
          <div id="projectSamplesList" class="list-group"></div>
        </div>

        <div class="tab-pane" id="projectNotes">

          <auth:projectNotesAdd>
          <h6><i class="icon-edit-sign"></i> {MESA_PLU_PROJECTNOTES} </h6>

          <div class="control-group" id="project_notes_cg">
            <div class="controls">
              <div >
                <select id="projectSampleNoteSelector" class="input-block-level">

                </select>
              </div>
            </div>
          </div>

          <div class="control-group" id="project_notes_cg">
            <div class="controls">
              <div class="input-block-level">
                <textarea id="projNotes" class="textarea-block-level" rows='5'></textarea>
              </div>
            </div>
          </div>

          <div id="noteAddDiv">
            <h6> {MESA_PLU_ADDSTANDARDNOTE} </h6>
            <table width="100%">


              <tr>
                <td>
                  <select class='input-block-level' id='projectNoteSelector'>
                    {project_notes}
                  </select>
                </td>
                <td style='width: 30px;text-align: center;'><button id='editPnote' type="button" class="btn btn-mini btn-primary"><i class='icon-edit'></i></td>
                  <td style='width: 30px;text-align: center;'><button id='remPnote' type="button" class="btn btn-mini btn-primary"><i class='icon-remove'></i></td>
                    <td style='width: 30px;text-align: center;'><button id='addPnote' type="button" class="btn btn-mini btn-primary"><i class='icon-plus'></i></td>
                    </tr>

                  </table>
                </div>

              </auth>

            </div>


            <div class="tab-pane" id="projectSocialTab">

            </div>

            <ul id="projectContentsNavTab" class="nav nav-tabs hide">
              <li class="active"><a href="#projectSamplesTab" data-toggle="tab" tabindex="-1" ><i class="icon-beaker"></i></a></li>
              <li class=""><a href="#projectNotes" data-toggle="tab" tabindex="-1" ><i class="icon-pencil"></i> {MESA_PLU_NOTES} <span id="project_note_found" class="badge badge-warning pull-right">!</span></a></li>
              <!-- <li class=""><a href="#projectSocialTab" data-toggle="tab" tabindex="-1" ><i class="icon-comments"></i> Social <span class="badge badge-primary">1</span>  </a></li>      -->
            </ul>
          </div>
        </div>
        <!-- End tab -->

      </div>

      <auth:notes> 
        <div class="well well-small" id="sampleNoteWell">
          <h6><i class="icon-pencil"></i> Monster notities</h6>
          {sample_notes}
        </div>
      </auth>
    </div>


    <!-- start right col -->
    <div class="span5">

      <div id="wrapper_for_right" class="follow-scroll">

          <div class="hide" id="sampleLoadingWell" style="text-align: center; padding: 50px;">
            <i class="icon icon-3x icon-spinner icon-spin"></i>
          </div>

          <div class="well well-small " id="sampleInfoWell">

            <h6>
              <i class="icon-star"></i> {MESA_PLU_SAMPLESANDRESULTS}
              <span class="pull-right">
                  <a class="btn btn-mini" id="jumpToLookup" tabindex="-1" href="#"><i class="icon-search"></i></a>
                  <auth:sampleRemoveButton>
                   <span id="removeSampleLi"><a class="btn btn-mini" id="removeSample" tabindex="-1" href="#" onClick="removeSelectedSample();"><i class="icon-remove"></i></a></spans>
                  </auth>
                  <auth:sampRevision>
                    <a id="sampleRevision" tabindex="-1" href="#" class="btn btn-mini"><i class="icon-rotate-left"></i></a>
                 </auth>
            </span>
          </h6>

          
          <div id="resultScrolDiv">
              <p class="subLead"> {MESA_PLU_SAMPLEINFO}</p>
             
               <div class="tabbable tabs-below">
                    <div class="tab-content">
                      <div class="tab-pane active" id="sampleInfoTab">
                          <span id="sampleInfoSpan">
                          </span>
                      </div>

                      <div class="tab-pane" id="metadataTab">
                          <span id="metadataSpan">
                          </span>

                          <p>
                            <auth:editSampleDetails>
                              <a id="metaDataAddButton" class="btn btn-mini btn-primary" >Metadata toevoegen</a>
                            </auth>
                          </p>
                      </div>

                      <div class="tab-pane" id="sampleFilesTab">
                      

                        <p>Bestanden toegevoegd aan monster</p>
                        <div id="sampleFilesDiv">

                        </div>

                        <p>Bestand toevoegen </p> 

                        <div id="drop_zone">Sleep bestanden hier voor upload</div>


                        <div><em>Of gebruik upload formulier</em></div> 
                        <div class="flex">

                            <input type="file" id="sampleFileUpload" name="samplefileupload"  class="input-block-level" />

                            <!-- <select id="fileVisibleForCustomer">
                                <option value="1">Zichtbaar voor klant (in portal)</option>
                                <option value="0">Niet zichtbaar voor klant</option>
                            </select>  -->

                            <a id="uploadFileToSampleButton" class="btn btn-mini btn-primary" >Uploaden</a>


                            </div>


                      </div>
                    </div>

                    
                    <ul class="nav nav-tabs">
                        <li class="active"><a href="#sampleInfoTab" data-toggle="tab" tabindex="-1" ><i class="icon-beaker"></i> Details</a></li>                        
                        <li class=""><a href="#metadataTab" data-toggle="tab" tabindex="-1" ><i class="icon-folder-open-alt"></i> Metadata <span id="metadata_found_badge" class="badge badge-warning pull-right">2</span></a></li>
                        <li class=""><a href="#sampleFilesTab" data-toggle="tab" tabindex="-1" ><i class="icon-file"></i> Bestanden <span id="files_found_badge" class="badge badge-warning pull-right"></span></a></li>
                    </ul>

                </div>
           

            

              <a href="#" id="backToBar">&nbsp; </a>

              <span id="sampleResultSpan">
              </span>
            </div>

            
        
          </div>

    </div>

    <!-- VV End right column -->
    </div>        

  </div>
  
  <div class="well"  id="projectSearchResultSpace">

      <div>
          <h6><i class="icon-suitcase"></i> {MESA_PLU_FOUNDPROJECTS} </h6>

          <table class="table table-condensed hltable" id="">
            <thead>
            <tr>
                <th>Status</th>
                <th>Referentie MAZ</th>
                <th>Revisie</th>
                <th>Referentie klant</th>
                <th>Aantal monsters</th>
                <th>Bemonsterdatum</th>
                <th>Ontvangstdatum</th>
                <th>Inzetdatum</th>                
            </tr>
            </thead>
            <tbody id="projectSearchTableBody">

            </tbody>
        </table>
        






    

        </div>
    

  </div>
</div>

<div id="editProjectModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="editProjectModalTitle" aria-hidden="true" style="z-index: 1041;">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="editProjectModalTitle">{MESA_PLU_EDITPROJECTINFO}</h3>
  </div>
  <div class="modal-body" id="projectEditDialogBody" >
    {project_fields}

    <span id="project_extra_load"><i class="icon icon-spin icon-spinner"></i></span>
    <span id="project_extra_edit"></span>

  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_PLU_CLOSE}</button>
  </div>
</div>

<div id="vetoResultModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="vetoResultModalTitle" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="vetoResultModalTitle">{MESA_PLU_SETVETORESULT}</h3>
  </div>
  <div class="modal-body" id="vetoResultDialogBody" >

    <div class="control-group" id="veto_cg">
      <div class="controls">
        <div class="input-prepend input-append input-block-level">
          <span class="add-on" id="veto_label"></span>
          <input type="text" id="veto_input" value="" class="input-block-level" placeholder="{MESA_PLU_ENTERVETORESULT}">
        </div>
      </div>
    </div>

    <div class="control-group" id="veto_dispo_cg">
      <div class="controls">
        <div class="input-prepend input-append input-block-level">
          <span class="add-on" id="veto_dispo_label">Type uitslag</span>          
            <select id="veto_dispo" class="input-block-level">
              <option value="null" selected="selected">Selecteer een van deze opties</option>
              <option value="+">Fout: Rood op rapport & portal - Deze waarde is Fout</option>
              <option value="-">Goed: Zwart op rapport & groen in portal - Deze waarde is een verwacht resultaat</option>
              <option value=".">Neutraal:  Zwart op rapport & grijs in portal - Aan deze waarde kan geen beoordeling worden gegeven</option>
            </select>
        </div>
      </div>
    </div>


    <div class="control-group" id="vetoreason_cg">
      <div class="controls">
        <div class="input-prepend input-append input-block-level">
          <span class="add-on" id="vetoreason_label">Reden wijziging:</span>
          <input type="text" id="vetoreason_input" value="" class="input-block-level" placeholder="">
        </div>
      </div>
    </div>

  </div>
  <div class="modal-footer">

    <button class="btn btn-warning" onClick="removeVeto();"><i class="icon-remove"></i> {MESA_PLU_REMOVEVETO}</button>
    <button class="btn btn-success" onClick="saveVeto();"><i class="icon-check"></i> {MESA_PLU_SAVEVETO}</button>
    <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_PLU_CLOSE}</button>
  </div>
</div>

<div id="noteAddModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="noteAddModalTitle" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="noteAddModalTitle">{MESA_PLU_ADDSTANDARDNOTE}</h3>
  </div>
  <div class="modal-body" id="noteAddDialogBody" >

    <div class="control-group" id="title_cg">
      <label class="control-label" for="noteTitle">{MESA_PLU_STANDARDNOTETITLE}</label>

      <div class="controls">
        <input type='text' id='noteTitle' class='input-block-level' />
      </div>
    </div>

    <div class="control-group" id="newNote_cg">
      <label class="control-label" for="noteContent">{MESA_PLU_STANDARDNOTECONTENT}</label>
      <div class="controls">
        <textarea id='noteContent' class='input-block-level'></textarea>
      </div>
    </div>

  </div>
  <div class="modal-footer">
    <button class="btn btn-success" onClick="saveNote();"><i class="icon-save"></i> {MESA_PLU_STANDARDNOTESAVE}</button>
    <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_PLU_CLOSE}</button>
  </div>
</div>

<div id="noteEditModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="noteEditModalTitle" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="noteEditModalTitle">{MESA_PLU_ADDSTANDARDNOTE}</h3>
  </div>
  <div class="modal-body" id="noteEditBody" >


    <div class="control-group" id="editNoteTitleCG">
      <label class="control-label" for="editNoteTitle">{MESA_PLU_STANDARDNOTETITLE}</label>

      <div class="controls">
        <input type='text' id='editNoteTitle' class='input-block-level' />
      </div>
    </div>

    <div class="control-group" id="editNoteCg">
      <label class="control-label" for="editNoteContent">{MESA_PLU_STANDARDNOTECONTENT}</label>
      <div class="controls">
        <textarea id='editNoteContent' class='input-block-level'></textarea>
      </div>
    </div>

    <input type='hidden' id='editNoteId'  />

  </div>
  <div class="modal-footer">
    <button class="btn btn-success" onClick="saveEditNote();"><i class="icon-save"></i> {MESA_PLU_STANDARDNOTESAVE}</button>
    <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_PLU_CLOSE}</button>
  </div>
</div>

<div id="revisionModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="revisionModalTitle" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="revisionModalTitle">{MESA_SLU_REVISIONS}</h3>
  </div>
  <div class="modal-body" id="revisionModalBody" >



  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SLU_CLOSE}</button>
  </div>
</div>

<div id="exportModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="exportModalTitle" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="exportModalTitle">Uitgevoerde rapporten</h3>
  </div>
  <div class="modal-body" id="exportModalBody" >



  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SLU_CLOSE}</button>
  </div>
</div>

<div id="clientWishesModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="clientWishesModalTitle" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="clientWishesModalTitle">Klant bijzonderhden & bestanden</h3>
  </div>
  <div class="modal-body" id="clientWishesModalBody" >

    <h5><i class="icon icon-paper-clip"></i> Bijzonderheden / wensen</h5>
    <div id="clientWishesNote">
    </div>

    <h5><i class="icon icon-upload"></i> Bestanden </h5>
    <div id="clientWishesFileList">

    </div>

  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SAD_CLOSE}</button>
  </div>
</div>


<div id="projectDeauthModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="projectDeauthModalTitle" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="projectDeauthModalTitle">Project deauthoriseren</h3>
  </div>
  <div class="modal-body" id="projectDeauthModalBody" >

     <div class="control-group" id="projectDeauthReasonCG">
        <div class="controls">
          <div class="input-prepend input-append input-block-level">
            <span class="add-on">Bron van de-authorisatie oorzaak</span>
            <select id='projectDeauthReason'>
                <option value="NULL">Selecteer een optie</option>
                <option value="Intern (oorzaak/fout bij MAZ)">Intern (oorzaak/fout bij MAZ)</option>
                <option value="Extern (oorzaak/fout bij klant)">Extern (oorzaak/fout bij klant)</option>
              </select>
          </div>
        </div>
      </div>

      <div class="control-group" id="projectDeauthReasonCG">
        <div class="controls">
          <div class="input-prepend input-append input-block-level">
            <span class="add-on">Reden van de-authorisatie</span>
            <input type="text" id='projectDeauthExplanation' />
          </div>
        </div>
      </div>
    

    </div>
 <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">Annuleren</button>
    <button class="btn btn-success" onClick="checkProjectDeauth();"><i class="icon-check"></i> Deauthoriseren </button>
    
  </div>
  </div>
 
</div>

<div id="metadataModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="metadataModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="metadataModalTitle">Metadata toevoegen</h3>
    </div>
    <div class="modal-body" id="metadataModalBody" >

       <div class="control-group" id="metadataNameCG" style="display:block;">
           <label class="control-label" for="metadataName">Metadata naam</label>
            <div class="control">
              <input type="text" id="metadataName" class="input-block-level" placeholder="Metadata naam" />
           </div>
       </div>

       <div class="control-group" id="metadataValueCG" style="display:block;">
           <label class="control-label" for="metadataValue">Metadata waarde</label>
            <div class="control">
              <input type="text" id="metadataValue" class="input-block-level" placeholder="Metadata waarde" />
           </div>
       </div>


    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SAD_CANCEL}</button>
        <button class="btn btn-primary" aria-hidden="true" onclick="saveMetadata();"><i class="icon-save"></i>Opslaan</button>
    </div>
</div>

<div id="projectUnlockModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="projectUnlockModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="projectUnlockModal">Project autorisatie vrijgeven</h3>
    </div>
    <div class="modal-body" id="projectUnlockBody">
  
      <p> U wilt dit project vrijgeven voor autorisatie. Dit betekent dat gebruikers met de juiste bevoegdheden dit project weer kunnen autoriseren.</p>
      

      <div class="control-group"  style="display:block;">
           <label class="control-label" for="lockReason">Reden voor deblokkeren</label>
            <div class="control">
              <input type="text" id="unlockReason" class="input-block-level" placeholder="Reden voor deblokkeren" />
           </div>
      </div>



    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SAD_CANCEL}</button>
        <button class="btn btn-primary" aria-hidden="true" onclick="doUnlockProject();"><i class="icon-unlock"></i>Vrijgeven</button>
    </div>
</div>


<div id="projectLockModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="projectLockModalTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="projectLockModalTitle">Project autorisatie blokkeren</h3>
    </div>
    
    <div class="modal-body" id="projecLockBody" >
        
      <p> U wilt dit project blokkeren voor autorisatie. Dit betekent dat niemand dit project kan autoriseren totdat u of een beheerder het project vrijgeeft.</p>
      
      <p> Geef hieronder een blokkerings reden  om andere gebruikers op de hoogte te brengen waarom dit project geblokkeerd is.</p>

       <div class="control-group"  style="display:block;">
           <label class="control-label" for="lockReason">Reden voor blokkeren</label>
            <div class="control">
              <input type="text" id="lockReason" class="input-block-level" placeholder="Reden voor blokkeren" />
           </div>
       </div>


    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SAD_CANCEL}</button>
        <button class="btn btn-primary" aria-hidden="true" onclick="doLockProject();"><i class="icon-lock"></i>Blokkeren</button>
    </div>
</div>



<div id="projectChangeClientModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="projectChangeClientTitle" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="projectChangeClientTitle">Project klant wijzigen</h3>
    </div>
    <div class="modal-body" >
        
      <p> U wilt dit project van klant wijzigen. Deze wijziging wordt direct uitgevoerd. Indien dit project al in de portal beschikbaar is zal deze hier ook direct van klant wijzigen.</p>
           

       <div class="control-group"  style="display:block;">
           <label class="control-label" for="lockReason">Nieuwe klant: </label>
            <div class="control">
              
            <input  id="change_project_client_selector" type="text"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off"
                spellcheck="false"
                class="select2-input select2-default input-block-level"
                tabindex="0"
                placeholder=""
                aria-activedescendant="select2-result-label-351"
              />
           </div>
       </div>

   


    

    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SAD_CANCEL}</button>
        <button class="btn btn-primary" aria-hidden="true" onclick="doChangeProjectClient();"><i class="icon-exchange"></i>Wijzigen</button>
    </div>
</div>



<div id="projectBeingChangedModal" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-labelledby="projectBeingChangedModalTitle" aria-hidden="true">
    <div class="modal-header">
      
        <h3 id="projectBeingChangedModalTitle">Bezig met klant wijziging</h3>
    </div>
    <div class="modal-body" style="text-align: center;">
                  
        <svg width="50" height="50" viewBox="0 0 105 105" xmlns="http://www.w3.org/2000/svg" fill="#000">
            <circle cx="12.5" cy="12.5" r="12.5">
                <animate attributeName="fill-opacity"
                begin="0s" dur="1s"
                values="1;.2;1" calcMode="linear"
                repeatCount="indefinite" />
            </circle>
            <circle cx="12.5" cy="52.5" r="12.5" fill-opacity=".5">
                <animate attributeName="fill-opacity"
                begin="100ms" dur="1s"
                values="1;.2;1" calcMode="linear"
                repeatCount="indefinite" />
            </circle>
            <circle cx="52.5" cy="12.5" r="12.5">
                <animate attributeName="fill-opacity"
                begin="300ms" dur="1s"
                values="1;.2;1" calcMode="linear"
                repeatCount="indefinite" />
            </circle>
            <circle cx="52.5" cy="52.5" r="12.5">
                <animate attributeName="fill-opacity"
                begin="600ms" dur="1s"
                values="1;.2;1" calcMode="linear"
                repeatCount="indefinite" />
            </circle>
            <circle cx="92.5" cy="12.5" r="12.5">
                <animate attributeName="fill-opacity"
                begin="800ms" dur="1s"
                values="1;.2;1" calcMode="linear"
                repeatCount="indefinite" />
            </circle>
            <circle cx="92.5" cy="52.5" r="12.5">
                <animate attributeName="fill-opacity"
                begin="400ms" dur="1s"
                values="1;.2;1" calcMode="linear"
                repeatCount="indefinite" />
            </circle>
            <circle cx="12.5" cy="92.5" r="12.5">
                <animate attributeName="fill-opacity"
                begin="700ms" dur="1s"
                values="1;.2;1" calcMode="linear"
                repeatCount="indefinite" />
            </circle>
            <circle cx="52.5" cy="92.5" r="12.5">
                <animate attributeName="fill-opacity"
                begin="500ms" dur="1s"
                values="1;.2;1" calcMode="linear"
                repeatCount="indefinite" />
            </circle>
            <circle cx="92.5" cy="92.5" r="12.5">
                <animate attributeName="fill-opacity"
                begin="200ms" dur="1s"
                values="1;.2;1" calcMode="linear"
                repeatCount="indefinite" />
            </circle>
        </svg>

        <p> Uw klant-wijziging wordt verwerkt, dit kan enige tijd duren...</p>
                         
    </div>
    <div class="modal-footer">
        
        <button id="client_change_ready" data-dismiss="modal" class="btn btn-primary" aria-hidden="true">Sluiten</button>
    </div>
</div>



<script>

selected_sample = false;
selected_barcode = false;
selected_client = false;
selected_subclient = 'sub';
selected_project = false;
selected_sample = false;
selected_sample_portal = null;

//switch
switch_project_client_to = null; 

selected_veto_said = false;
selected_veto_parameter = false;
selected_veto_border = false;

var project_authorised = false;
entry_project = '{entry_project}';


// Prevent default drag behaviors
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
  document.getElementById('drop_zone').addEventListener(eventName, preventDefaults, false)
})

function preventDefaults (e) {
  e.preventDefault()
  e.stopPropagation()
}



function highlight(e) {
  document.getElementById('drop_zone').classList.add('highlight')
}

function unhighlight(e) {
  document.getElementById('drop_zone').classList.remove('highlight')
}


// Handle dropped files
document.getElementById('drop_zone').addEventListener('drop', handleDrop, false)

function handleDrop(e) {

  let dt = e.dataTransfer
  let files = dt.files
  
  handleFiles(files)
}

function handleFiles(files) {

  
  ([...files]).forEach(function(file) {
    
    //ask for the visibility
    // ('Wilt u dit bestand: ' + file.name + ' zichtbaar maken voor de klant?', 'Nee', function(result){
    //   uploadFile(file, result);
    // });

    uploadFile(file, true);
    

  });
}

function uploadFile(file, visibility) {
    

    let formData = new FormData();
        
    //is there a file?
    if(file == undefined){
      alert('Geen bestand geselecteerd');
      return;
    }
      
    formData.append('file', file);
    formData.append('sampleId', selected_sample);
    formData.append('clientId', selected_client);
    formData.append('visibleForClient', (visibility) ? '1' : '0');

    //submit via xhr
    $.ajax({
      url: "{LB}/sampleFiles/storeFile",
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(data){
        console.log(data);
        loadSampleFiles(selected_sample);
      },
      error: function(data){
        alert('Er ging iets mis bij het uploaden van het bestand');
        console.log(data);
      }
    });
  
}


$(function(){



  // Highlight drop area when item is dragged over it
  ['dragenter', 'dragover'].forEach(eventName => {
    document.getElementById('drop_zone').addEventListener(eventName, highlight, false)
  });

  ['dragleave', 'drop'].forEach(eventName => {
    console.log(eventName);
    document.getElementById('drop_zone').addEventListener(eventName, unhighlight, false)
  });

  scrollHeight = defineScrollHeight();

  $('#resultScrolDiv').slimScroll({
    height: scrollHeight,
    railVisible: false,
    railOpacity: 0
  });

  $('#proj_search_sample').focus();

  $('#backToBar').on('focus', function(){
    $('#proj_search_sample').focus();
  });

  //on click select all the text
  $('#proj_search_sample').on('click', function(){
    this.select();
  });

  $('#proj_search_sample').on('focus', function(){
    this.select();
  });


  $('#projectSampleNoteSelector').on('change', function(){
    var val = $(this).val();
    if(val != 'NULL'){
      loadSampleNote(selected_project, val);
    }
  });

  $('#proj_search_billing').enterKey(function () {
    backToSearch();
    searchProjects('byReferenceForBilling');
  });


  $('#proj_search_ref').enterKey(function () {
    backToSearch();
    searchProjects('byReference');
  });

  $('#proj_search_sample').enterKey(function () {
    backToSearch();
    searchProjects('bySample');
  });


  $('#middleWell').hide();
  $('#sampleInfoWell').hide();
  $('#sampleNoteWell').hide();
  $('#projWell').hide();
  $('#projectSearchResultSpace').hide();

  $('#lockProject').hide();
  $('#unlockProject').hide();
  
  $('#client_name').focus();

  


  $('#change_project_client_selector').select2({
    minimumInputLength: 2,
    placeholder: "{MESA_PLU_SELECTCLIENT}",

    ajax: {
      type: "POST",
      url: "{LB}/clients/predict",
      dataType: 'json',
      quietMillis: 350,
      data: function (term, page) {
        return {
          term: term, //search term
          page_limit: 10 // page size
        };
      },
      results: function (data, page) {
        return { results: data.results };
      }

    },
    initSelection: function(element, callback) {
      return $.getJSON("{LB}/clients/predictInit/" + (element.val()), null, function(data) {
        return callback(data);
      });
    },
    dropdownCssClass: "bigdrop"
  }).on('change', function(){

    switch_project_client_to = $(this).val();
  
  });


  $("#client_name").select2({
    minimumInputLength: 2,
    placeholder: "{MESA_PLU_SELECTCLIENT}",

    ajax: {
      type: "POST",
      url: "{LB}/clients/predict",
      dataType: 'json',
      quietMillis: 350,
      data: function (term, page) {
        return {
          term: term, //search term
          page_limit: 10 // page size
        };
      },
      results: function (data, page) {
        return { results: data.results };
      }

    },
    initSelection: function(element, callback) {
      return $.getJSON("{LB}/clients/predictInit/" + (element.val()), null, function(data) {
        return callback(data);
      });
    },
    dropdownCssClass: "bigdrop"
  }).on('change', function(){

    //check if its empty
    if ($(this).val() == ''){
      $('#client').val('NULL');
      $(this).val('');
      $("#subclient").val('NULL');
      return;
    }

    //breakup the tag and select client id + subclient id
    else{
      clSplit = $(this).val().split("||");
      selected_client = clSplit[0];
      selected_subclient = 'null';
      $('#client').val(selected_client);
      $('#subclient').val(selected_subclient);
    }

    $('#searchByClientButton').focus();

  });

  if(entry_project != 'null'){
    //$('#client_name').val('{entry_client_name}');
    //loadSubClients('{entry_client}', '{entry_subclient}');
    //set hidden values for pleasentness
    $("#client_name").select2("val", "{entry_client}||{entry_subclient}");
    selected_project = '{entry_project}';
    selected_client = '{entry_client}';
    //selected_subclient = '{entry_subclient}';
    $('#client').val('{entry_client}');
    //$('#subclient').val(selected_subclient);
    loadSelectedProject(selected_client, selected_project);
  }

  $('#subclient').change(function() {

  });


  $('#uploadFileToSampleButton').on('click', function(){
      let formData = new FormData();
      
      let file = $('#sampleFileUpload')[0].files[0];

      //is there a file?
      if(file == undefined){
        alert('Geen bestand geselecteerd');
        return;
      }

    
      formData.append('file', file);
      formData.append('sampleId', selected_sample);
      formData.append('clientId', selected_client);
      //formData.append('visibleForClient', $('#fileVisibleForCustomer').val());

      //submit via xhr
      $.ajax({
        url: "{LB}/sampleFiles/storeFile",
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(data){
          console.log(data);
          loadSampleFiles(selected_sample);
        }
      });

      console.log(file);
  });

  $('#projNotes').change(function(){
    saveProjectNotes();
  });

  $('#searchByClientButton').click(function(){
    backToSearch();
    searchProjects('byClient');
  });

  $('#searchByDateButton').click(function(){
    backToSearch();
    searchProjects('byDate');
  });

  $('#searchBySampleButton').click(function(){
    backToSearch();
    searchProjects('bySample');
  });

  $('#searchByReferenceButton').click(function(){
    backToSearch();
    searchProjects('byReference');
  });


  $('#jumpToLookup').click(function(){
    window.location.replace("{LB}/samples/lookup/" + selected_barcode );
  });

  $('#customerProjects').on('click', '.list-group-item', function(){
    //$(this).css('font-style', 'italic');
    //loadSelectedProject($(this).attr('clientId'), $(this).attr('projectId'));
  });

  $('#projectSearchTableBody').on('click', 'tr td', function(){
    

    //if this is a td find the parent tr
    if($(this).is('td')){
      var tr = $(this).closest('tr');
    }
    else{
      var tr = $(this);
    }

    //cancel if this tr has the class billing-line
    if($(tr).hasClass('billing-line')){
      return false;
    }

    //remove all italic and bold from the parent table
    $('#projectSearchTableBody').find('tr').each(function(){
        $(this).css('font-style', 'normal');
        $(this).css('font-weight', 'normal');
    });



    //apply italic    
    $(tr).css('font-style', 'italic');
    $(tr).css('font-weight', 'bold');

    //find the icon icon-eye-open and remove the hidden clas
    $(tr).find('.icon-eye-open').removeClass('hidden');



    //$(tr).css('font-style', 'italic');

    loadSelectedProject($(tr).attr('clientId'), $(tr).attr('projectId'));
  });

  $('#projectSamplesList').on('click', '.list-group-item', function(){
    var sampleId = $(this).attr('sampleId');
    var barcode = $(this).attr('barcode');
    //selected_sample = sampleId;
    selected_barcode = barcode;
    $("#projectSamplesList").find('.list-group-item').removeClass('active');
    $(this).addClass('active');
    loadSelectedSample(sampleId);
  });

  //note update hook
  $('#sampleNoteWell').on('change', 'textarea', function(){
    var value = $(this).val();
    var field = 'sample_notes';
    $.ajax({
      type: "POST",
      data: { id: selected_sample, field: field, value: value},
      url: "{LB}/samples/updateSampleNoteField"
    });
  });

  //sample update hook
  $('#sampleInfoSpan').on('change', 'input, textarea, select', function(){

    var field = $(this).attr('name');
    var value = $(this).val();
    var isExtra = false;

    if($(this).hasClass('sample-extra-field')){
      isExtra = true;
    }

    $.ajax({
      type: "POST",
      data: { id: selected_sample, field: field, value: value, extra: isExtra},
      url: "{LB}/samples/updateSampleField"
    }).done(function(msg) {

    });
  });

  $('.project_date_field').on('blur', function(){
    $(this).change();
  });

  $('.project_input_field').on('change', function(){
    var thisField = $(this).attr('project_field_name');
    var thisValue = $(this).val();

    $.ajax({
      type: "POST",
      url: "{LB}/projects/updateProjectField/" + selected_project ,
      data: { field: thisField, value: thisValue}
    }).done(function(response) {
    });
  });


  $('#editProjectModal').on('change', '.project_extra_field', function(){
    var thisField = $(this).attr('project_field_name');
    var thisValue = $(this).val();

    $.ajax({
      type: "POST",
      url: "{LB}/projects/updateProjectExtraField/" + selected_project + "/" + thisField + "/" + thisValue
    }).done(function(response) {

    });

  });

  $('#editProjectDetailsBtn').on('click', function(){
    loadProjectEditWindow();
  });

  $('#sampleResultSpan').on('click', '.btn-veto', function(){
    var said = $(this).attr('said');
    var parameter = $(this).attr('parameter');
    var currentValue = $(this).attr('originalValue');
    var currentReason = $(this).attr('currentReason');
    initVeto(said, parameter, currentValue, currentReason);
  });

  $('#projReport').on('click', function(){
    popUp('{LB}/results/projOverview/' + selected_project, 'projOverview', 640, 700);
  });

  $('#editProjectModal').on('hidden', function () {
    loadSelectedProject(selected_client, selected_project);
  });

  $('#addPnote').on('click', function(){
    $('#noteAddModal').modal('show');
  });

  $('#editPnote').on('click', function(){
    var selectedNote = $('#projectNoteSelector').val();

    if(selectedNote == 'NULL'){
      return false;
    } else{

      $.ajax({
        type: "POST",
        dataType: "json",
        url: "{LB}/projectNotes/fetchNoteJSON/" +  selectedNote
      }).done(function(msg) {

        $('#editNoteTitle').val(msg['name']);
        $('#editNoteContent').val(msg['content']);
        $('#editNoteId').val(msg['id']);
        $('#noteEditModal').modal('show');

      });

    }
  });

  $('#remPnote').on('click', function(){
    var selectedNote = $('#projectNoteSelector').val();

    if(selectedNote == 'NULL'){
      return false;
    } else{
      removeNote(selectedNote);     
    }
  });

  $('#projectNoteSelector').on('change', function(){
    var selectedNote = $(this).val();
    if(selectedNote == 'NULL'){
      return false;
    } else{
      useNote(selectedNote);
    }
  });


  $('#sampleRevision').on('click', function(){

    $.ajax({
      type: "POST",
      url: "{LB}/changeTracker/changesForSample/" +  selected_sample
    }).done(function(msg) {
      $('#revisionModalBody').html(msg);
      $('#revisionModal').modal('show');
    });

  });

  $('#projectHardcopies').on('click', function(){
    $.ajax({
      type: "POST",
      url: "{LB}/exports/projectReportOverview/" +  selected_project
    }).done(function(msg) {
      $('#exportModalBody').html(msg);
      $('#exportModal').modal('show');
    });
  });

  $('#projectRevisions').on('click', function(){

    $.ajax({
      type: "POST",
      url: "{LB}/changeTracker/changesForProject/" +  selected_project
    }).done(function(msg) {
      $('#revisionModalBody').html(msg);
      $('#revisionModal').modal('show');
    });
  });

  $('#projectFollow').on('click', function(){
    if($(this).attr('following') == 1){
      unfollowProject();
    } else{
      followProject();
    }
  });


  $('#metadataSpan').on('change', 'input, textarea', function(){
    $.ajax({
      type: "POST",
      url:  "{LB}/metadata/change",
      data: { 'id': $(this).attr('metaid'), 'type': $(this).attr('metatype'), 'sample':  $(this).attr('sample'), 'value' : $(this).val() },
    }).done(function() {
    });
  });

  $('#metaDataAddButton').on('click', function(){
    $('#metadataModal').modal('show');    
  });

  $('#closeLockAlertBtn').on('click', function(){
    backToSearch();
    $('#sampleLockDiv').hide();
    $('#projectPageShow').show();
  });

  $('a[data-toggle="tab"]').on('shown', function (e) {    
    var tabTarget = $(e.target).attr('href');
    if(tabTarget == '#metadataTab' && selected_sample_portal == true)
    {
      bootbox.alert('Let op, dit monster is afkomstig uit de portal. Deze meta-data is zodoende aangeleverd door de klant. Wijzig met zorg. ');
    }
    
  })


  //free selected sample
  $(window).on('beforeunload', function() {
    
    navigator.sendBeacon("{LB}/keyrings/removeOwnLock/PROJECT/" +  selected_project );
    navigator.sendBeacon("{LB}/keyrings/removeOwnLock/SAMPLE/" +  selected_sample );
    
  });


    $("#projectPageShow").on('keypress keyup blur', '.int-filter', function(e) {        

          $(this).val($(this).val().replace(/[^0-9,]/g,''));
   
    });


    $('#sampleInfoWell').on('click', '#client_description',  function(e) { 

        //<auth:clientDescription>

        if($('#client_description').prop('readonly') === true && project_authorised == false ) // && project_authorised == false   removed because was not implemented properly, and always false. 
        {

          if(confirm("Deze omschrijving is door de klant ingevoerd. Weet u zeker dat u deze wilt wijzigen?") == true) {
            $('#client_description').prop('readonly', false);
            $('#client_description').css("background-color", "white");  
          }
          
          
        }

        //</auth> 

        
    });

    $('#changeProjectClient').on('click', function(){

      $('#projectChangeClientModal').modal('show');

    });

    $('#projectChangeClientModal').on('hidden', function () {

      
      $("#change_project_client_selector").select2("val", '');
      switch_project_client_to  = null; 
      
    });



});

function lockProject(){
  $('#projectLockModal').modal('show');
}

function unlockProject(){
  $('#projectUnlockModal').modal('show');
}

function doUnlockProject(){

  var unlockReason = $('#unlockReason').val();

  unlockReason = unlockReason.replace(/^\s+|\s+$/g, '');

  if(unlockReason == ''){
    alert('Vul een reden in');
    return false;
  }
    
  $('#projectUnlockModal').modal('hide');
      
    $.ajax({
      type: "POST",      
      url: "{LB}/projects/unlockProject",
      dataType: 'json',
      data: {
        'project_id' : selected_project,    
        'unlock_reason' : unlockReason
      },
    }).done(function(msg) {          
        loadSelectedProject(selected_client,selected_project );    
    });

}

function doLockProject(){

  var lockReason = $('#lockReason').val();
  
  lockReason = lockReason.replace(/^\s+|\s+$/g, '');
  
  if(lockReason == ''){
    alert('Vul een reden in');
    return false;
  }
    
  $('#projectLockModal').modal('hide');
  
  

  $.ajax({
      type: "POST",
      url: "{LB}/projects/lockProject",
      data: {
        'project_id' : selected_project,
        'lock_reason' : lockReason
      },
    }).done(function(msg) {
      loadSelectedProject(selected_client,selected_project );
  });

}

function defineScrollHeight(){
  height = $(window).height() - $('.navbar-fixed-top').height() - 100;
  return height;
}

function removeProject(){

  bootbox.confirm("<h3> {MESA_PLU_PROJECTREMOVETITLE} </h3> <p>{MESA_PLU_PROJECTREMOVEMSG}</p>", function(result) {
    if(result == true){
      $.ajax({
        type: "POST",
        url: "{LB}/projects/removeProject/" + selected_project
      }).done(function(msg) {
        backToSearch();
      });
    }
  });
}

function removeSelectedSample(){

  if(selected_sample_portal === true){
    var random_check = Math.floor(Math.random() * (2000 - 1000 + 1) + 1000);
    bootbox.prompt("<h3>{MESA_PLU_DELETESAMPLETITLE}</h3> <p>{MESA_PLU_DELETESAMPLEAREYOUSURE}</p> <p> <span class='label label-warning'>Dit monster komt uit de MAZ portal</span>, daarom is extra controle vereist. Bij het verwijderen verliest de klant alle instellingen zoals product groepen en metadata, toch doorgaan? Bevestigings code:<span class='label label-warning'>" + random_check + "</span>", function(result) {
        if (result != null) {
            if (result == random_check) {
              doRemoveSample();
            } else {
                bootbox.alert("<h3>{MESA_CLI_REMOVEERRORTITLE}</h3> <p> Niet verwijderd, bevestigings code correct? </p> ");
            }
        }
    });
  }

  else
  {
    bootbox.confirm("<h3> {MESA_PLU_DELETESAMPLETITLE} </h3> <p>{MESA_PLU_DELETESAMPLEAREYOUSURE}</p>", function(result) {
    if(result == true){
      doRemoveSample();
    }
  });  
  }
  
}

function doRemoveSample(){
  $.ajax({
    type: "POST",
    url: "{LB}/samples/removeSample/" + selected_sample
  }).done(function(msg) {
    loadSelectedProject(selected_client, selected_project);
  });
}

function loadSelectedSample(sampleId){

  console.log('Loading sample:' + sampleId);
  previous_sample = selected_sample;  //for lock releasing
  console.log('should remove lock for:' + previous_sample);
  selected_sample = sampleId;


  $('#sampleLoadingWell').show();
  $('#sampleInfoWell').hide();
  $('#sampleNoteWell').hide();

  $.ajax({
    type: "POST",
    data: {'previous_sample' : previous_sample},
    dataType: 'json',
    url: "{LB}/samples/sampleInProjectOverview/" + sampleId
  }).done(function(msg) {

    if(msg['sample_source'] === '3')
    {
      selected_sample_portal = true;
    }

    else
    {
      selected_sample_portal = false;
    }
    

    $('#sampleLoadingWell').hide();
    $('#sampleInfoWell').show();
    $('#sample_notes').val(msg['sample_notes']);
    $('#portal_notes').html(msg['portal_notes']);
    $('#sampleNoteWell').show();

    $('#sampleInfoSpan').html(msg['sampleInfo']);
    $('#sampleResultSpan').html(msg['assay_results']);    
    $('#metadata_found_badge').html(msg['metadata_found']);           
    $('#metadataSpan').html(msg['metadata_info']);

    $('#resultScrolDiv').slimScroll({ scrollTo:  '0px' });
    $('#details').focus();
    $('#sampleInfoWell').highLight();

    loadSampleFiles(sampleId);

  });

  

}

function loadSelectedProject(clientId, projectId, loadInSample){

  previous_project = selected_project;    //for lock release

  selected_project = projectId;
  selected_client = clientId;

  $.ajax({
    type: "POST",
    data: {previous_project: previous_project},
    dataType: 'json',
    url: "{LB}/projects/getProjectInformation/" + projectId
  }).done(function(options) {

    $('[href="#sampleInfoTab"]').tab('show');

    $('#middleWell').show();
    $('#sampleInfoSpan').empty();
    $('#sampleResultSpan').empty();
    $('#projectSearchSpace').hide();
    $('#projectSearchResultSpace').hide();
    $("#selectedProjectTable").html(options['project_block']);
    $('#projNotes').val(options['project_notes']);
    $('#projectSamplesList').empty().append(options['sample_block']);
    $('#selectedProjectSpace').show();
    $('#projWell').show();    
    $('#backToSearchButton').focus();
    $('#projectContentsNavTab').fadeIn(500);
    $('#projectSampleNoteSelector').html(options['project_samples_dropdown']);

    checkClientWishes(clientId);
    checkClientIsPortalUser(clientId);

    if(options['notes_present'] == true){
        $('#project_note_found').html('!');
    } else{
      $('#project_note_found').html('');
    }

    if(options['following_project'] == true){
      $('#projectFollow').attr('following', 1);
      $('#projectFollowIcon').removeClass('icon-bookmark-empty');
      $('#projectFollowIcon').addClass('icon-bookmark');
      $('#projectFollowIcon').addClass('icon-red');
    } else{
      $('#projectFollowIcon').removeClass('icon-red');
      $('#projectFollowIcon').removeClass('icon-bookmark');
      $('#projectFollowIcon').addClass('icon-bookmark-empty');
      $('#projectFollow').attr('following', 0);
    }

    if(options['locked'] == '1')
    {
      
      $('#blockByName').html(options['auth_lock_object']['locked_by_name']);
      $('#blockByAvatar').attr('src', options['auth_lock_object']['locked_by_avatar'] );
      $('#blockReason').html(options['auth_lock_object']['lock_reason'])
      $('#blockByDiv').show();
      
      $('#lockProject').hide();
      $('#unlockProject').show();
    }

    else{    
      $('#lockProject').show();
      $('#unlockProject').hide();
      $('#blockByDiv').hide();
    }

    if(options['project_auth'] == '0'){
 
      $('#projDeauth').hide();
      $('#projAuth').show();
      $('#syncForce').hide();
      $('#quietAuth').show();
      $('#projRemove').show();
      $('#removeSampleLi').show();
      $('#projNotes').prop('disabled', false);
      $('#projNotes').attr('disabled', false);
      $('#noteAddDiv').show();
      $('#editProjectDetailsBtn').show();
      $('#changeProjectClient').show();
      project_authorised = false; 
    }

    if(options['project_auth'] == '1'){
      
      $('#lockProject').hide();
      $('#unlockProject').hide();

      $('#projAuth').hide();
      $('#syncForce').show();
      $('#quietAuth').hide();
      $('#projDeauth').show();      
      $('#projRemove').hide();
      $('#removeSampleLi').hide();
      $('#projNotes').prop('disabled', true);
      $('#projNotes').attr('disabled', true);
      $('#noteAddDiv').hide();
      $('#editProjectDetailsBtn').hide();
      $('#changeProjectClient').hide();
      project_authorised = true;
    }

    if(options['lock_object']['locked'] == true){
      $('#projAuth').hide();
      $('#projDeauth').hide();
      $('#quietAuth').hide();
      $('#projRemove').hide();
      $('#removeSampleLi').hide();
      $('#changeProjectClient').hide();
      $('#projNotes').prop('disabled', true);
      $('#projNotes').attr('disabled', true);
      $('#noteAddDiv').hide();
      $('#editProjectDetailsBtn').hide();
      projectLockCall(options['lock_object']['locked_by_id'], options['lock_object']['locked_by_name'],  options['lock_object']['locked_by_avatar']);

    } else{
      $('#sampleLockDiv').hide();
    }

    if(options['locked'] == '1')
    {
      $('#projAuth').hide();
      $('#projDeauth').hide();
      $('#quietAuth').hide();
    }

    $('#projWell').highLight();

    if(loadInSample != undefined){
      $("a[sampleid='" + loadInSample +"'").trigger('click');
    }

  }).error(function(response) {
    $.playSound('{LP}/snd/scanDeny.wav');
    alert('{MESA_PLU_NOTFOUND}');
  });


}




function projectLockCall(user_id, user, user_avatar){
  $.playSound('{LP}/snd/scanLocked.wav');
  $('#usageName').html(user);
  $('#usageAvatar').attr('src',user_avatar );
  $('#sampleLockDiv').show();
  $('#resModButtons').hide();
  $('#editSampleButton').hide();
  $('#projectPageShow').hide();
}

function loadProjectEditWindow(){

  console.log('loading edit window');

  $.ajax({
    dataType: "json",
    type: "POST",
    url: "{LB}/projects/loadProjectFields/" + selected_project
  }).done(function(response) {
    $.each(response, function(index, value) {
      $('#pj_' + index).val(value);
    });
    $('#editProjectModal').modal('show');
  }).fail(function(msg){
    console.log('got no json object.');
  });

  $('#project_extra_load').show();
  $.ajax({
    dataType: "json",
    type: "POST",
    url: "{LB}/projects/renderExtraFields/" + selected_project
  }).done(function(response) {
    $('#project_extra_edit').html(response['extra_html']);
    $('#project_extra_load').hide();
  }).fail(function(response){
    console.log('Error in load extra fields');
  });


}

function loadSubClients(client, preselected) {
  /* Deprecated */ 
}

function searchProjects(searchType){

  var thisClient = $('#client').val();
  var thisSubClient = $('#subclient').val();
  var thisScope = $('#projectRange').val();

  var projBegin = $('#proj_search_begin').val();
  var projEnd = $('#proj_search_end').val();
  var projSearchSample = $('#proj_search_sample').val();
  var projectReference = $('#proj_search_ref').val();
  var projectBilling = $('#proj_search_billing').val();

  if(searchType == 'byClient'){
  
    if(thisClient == 'NULL' ){
      console.log('not valid');
    } else {
      loadCustomerProjects(thisClient, 'geen', thisScope);
    }
  }

  if(searchType == 'byDate'){
    loadCustomerProjects(false, false, projBegin, projEnd);
  }

  if(searchType == 'bySample'){
    console.log('load by sample, sample is:' + projSearchSample );
    loadCustomerProjects(false, false, false, false, projSearchSample );
  }

  if(searchType == 'byReferenceForBilling'){
    console.log('load by ref FOR billing, sample is:' + projSearchSample );
    loadCustomerProjects(false, false, false, false, false, projectBilling, true );
  }

  if(searchType == 'byReference'){
    console.log('Search by reference')
    loadCustomerProjects(false, false, false, false, false, projectReference );
  }
}


function loadCustomerProjects(clientId, subClient, span, span_end, sample, reference, billing){

  var url;
  var postFocus = false;

  if(span != undefined){
    url = "{LB}/projects/clientProjectsList/" + clientId + '/' + subClient + '/' + span;
  } if (span_end != undefined){
    url = "{LB}/projects/clientProjectsList/" + clientId + '/' + subClient + '/' + span + '/' + span_end;
  } if(sample != undefined){
    url = "{LB}/projects/clientProjectsList/" + clientId + '/' + subClient + '/' + span + '/' + span_end + '/' + sample;
    postFocus = true;
  } if(reference != undefined){
    url = "{LB}/projects/referenceSearch/" + reference;
  } if(billing != undefined){
    url = "{LB}/projects/billingSearch/" + reference;
  }


  $.ajax({
    type: "POST",
    dataType: 'json',
    url: url
  }).done(function(msg) {



    $('#projectSearchResultSpace').show();        
    
    $('#projectSearchTableBody').empty().append(msg['panel']);


    $('#projectSearchSpace').highLight();

    if(postFocus == true){
      if(msg['found'] == true){
        loadSelectedProject(msg['found_client'], msg['found_id'], msg['sample_id']);        
      }
    }


  });
}

function syncPortal(){
  $.ajax({
    type: "POST",    
    dataType: 'json',    
    url: '{LB}/portal/forceSync/' + selected_project
  }).done(function(msg) {
    alert('Project-sync succesvol aangevraagd.');
  }).error(function(response) {
    $.playSound('{LP}/snd/scanDeny.wav');
    console.warn(response);
    alert('Er ging iets mis bij het aanvragen van een sync naar d eportal');
  });
}

function silentAuth(){
  var random_check = Math.floor(Math.random() * (2000 - 1000 + 1) + 1000);
  bootbox.prompt("<h3>LET OP!! Weet u het zeker?</h3> <p>Bij een stille autorisatie worden er geen notificaties verzonden naar client-portal gebruikers! <br /> Om door te gaan voer het volgende getal in als bevestiging: </p> <p><span class='label label-warning'>" + random_check + "</span>", function(result) {
    if (result != null) {
        if (result == random_check) {        
          authProject(true);
        } else {
          bootbox.alert("<h3>Actie niet uitgevoerd!</h3> <p> Controle getal was niet correct. </p> ");
        }
    }
  }); 
}

function retractPortal(){

  var random_check = Math.floor(Math.random() * (2000 - 1000 + 1) + 1000);
  bootbox.prompt("<h3>LET OP!! Weet u het zeker?</h3> <p>U staat op het punt om alle resultaten van dit project uit de client-portal te halen. <br /> Om door te gaan voer het volgende getal in als bevestiging: </p> <p><span class='label label-warning'>" + random_check + "</span>", function(result) {
    if (result != null) {
        if (result == random_check) {
          $.ajax({
            type: "POST",    
            dataType: 'json',    
            url: '{LB}/portal/flushProject/' + selected_project
          }).done(function(msg) {
            alert('Resultaten succesvol terug getrokken');
          }).error(function(response) {
            $.playSound('{LP}/snd/scanDeny.wav');
            console.warn(response);
            alert('Er ging iets mis in het terug halen van de portal gegevens');
          });


        } else {
            bootbox.alert("<h3>Actie niet uitgevoerd!</h3> <p> Controle getal was niet correct. </p> ");
        }
    }
  }); 
}

function authProject(quiet){
  setProjectAuth('1', quiet);
}

function deauthProject(){
  setProjectAuth('0', false);
}

function clearSelectedProject(){
}

function doSetProjectAuth(authVal, origin, reason, quiet){

  var req;
  var dataObj = {};

  
  
  if(authVal == '1'){
    $('#projAuth').hide();
    $('#projDeauth').show();
    $('#syncForce').show();
    $('#quietAuth').hide();
    req = "{LB}/projects/authoriseProject/" + selected_project;
    dataObj = {quiet: quiet};
  } else {
    $('#quietAuth').show();
    $('#projAuth').show();
    $('#projDeauth').hide();
    $('#syncForce').hide();
    req = "{LB}/projects/deauthoriseProject/" + selected_project;
    dataObj = {reason: reason, origin: origin}
  }

  $.ajax({
    type: "POST",
    url:  req,
    data: dataObj,
  }).done(function() {
    if(authVal == '1'){
      window.location.href = '{LB}/projects/viewCompleted';
    } else{
      loadSelectedProject(selected_client,selected_project );
    }

  });
}

function setProjectAuth(authVal, quiet){


  if(authVal == '1'){

    $.ajax({
      type: "POST",
      dataType: "JSON",
      url:  "{LB}/projects/checkProjectComplete/" + selected_project
    }).done(function(msg) {

      if(msg['project_complete'] == true){
        doSetProjectAuth(1, false, false, quiet);
      } else{

        var messageToShow = ''

        if(msg['innoc_date_valid'] == false){
          alert('{MESA_PLU_PROJECTINNOCWEIRD}');
          return;
        }

        if(msg['results_complete'] == false && msg['details_complete'] == false){
          messageToShow = '{MESA_PLU_PROJECTNOTREADYBOTH}';
        }

        if(msg['results_complete'] == true && msg['details_complete'] == false){
          messageToShow = '{MESA_PLU_PROJECTNOTREADYDESC}';
        }

        if(msg['results_complete'] == false && msg['details_complete'] == true){
          messageToShow = '{MESA_PLU_PROJECTNOTREADY}';
        }

        //do not procceed if no note was set. 


        var authConfirm = confirm(''+messageToShow);

        if (authConfirm == true) {
          
          if(msg['results_complete'] == false){
            doSetProjectAuth(1, false, false, quiet);
          } 
          
          else{
            doSetProjectAuth(1, false, false, quiet);
          }
          
        } else {
          return;
        }
      }
    });
  } else{
    
    //show deauth box 
    $('#projectDeauthModal').modal('show');
  }
}

function checkProjectDeauth(){

  var origin = $('#projectDeauthReason').val();
  var reason = $('#projectDeauthExplanation').val();
  
  if(origin == 'NULL' ){
    alert('Selecteer deauthorisatie oorzaak bron in!');
  } else{
    if (reason.trim() == ''){
      alert('Voer een deauthorisatie reden in!');
    } else{
        $('#projectDeauthModal').modal('hide');
        $('#projectDeauthReason').val('');
        $('#projectDeauthExplanation').val('');
        doSetProjectAuth(0, origin, reason);
    }    
  }
  //projectDeauthReason   projectDeauthReason
}

function backToSearch(){
  $("#selectedProjectTable").html('');
  $('#selectedProjectSpace').hide();
  $('#projectSearchResultSpace').show();
  $('#sampleInfoSpan').empty();
  $('#sampleResultSpan').empty();
  $('#projectSamplesList').empty();
  $('#middleWell').hide();
  $('#sampleInfoWell').hide();
  $('#projWell').hide();

  //should release keylock and sample
  $.ajax({
    type: "POST",
    url:  "{LB}/keyrings/removeOwnLock/PROJECT/" + selected_project
  });

  $.ajax({
    type: "POST",
    url:  "{LB}/keyrings/removeOwnLock/SAMPLE/" + selected_sample
  });

  selected_sample = false;
  selected_barcode = false;
  selected_client = false;
  selected_project = false;
  selected_sample = false;
  selected_veto_said = false;
  selected_veto_parameter = false;
  project_authorised = false;
}

// function initVeto(said, parameter, currentValue, currentReason){
//   selected_veto_said = said;
//   selected_veto_parameter = parameter;
//   $('#veto_label').html(parameter);
//   $('#veto_input').val(currentValue);
//   $('#vetoreason_input').val(currentReason);
//   $('#vetoResultModal').modal('show');
// }

function initVeto(said, parameter){
  $.ajax({
    type: "POST",
    dataType: "json",
    data: { said: said, parameter: parameter},
    url: "{LB}/vetoResults/fetchVeto"
  }).done(function(msg) {
    
      console.log(msg);
      selected_veto_said = said;
      selected_veto_parameter = parameter;      
      

      $('#veto_label').html(parameter);
      $('#veto_input').val(msg['result']);
      $('#vetoreason_input').val(msg['reason']);

      //show disposition seelctor only on border-reactions
      if(msg['border'] === true)
      {
        
        selected_veto_border = true; 

        if(msg['disposition'] === null){
          $('#veto_dispo').val('null');
        }         
        else
        {
          $('#veto_dispo').val(msg['disposition']);
        }
        
        $('#veto_dispo_cg').show();
      }

      else
      {
        $('#veto_dispo_cg').hide();
        selected_veto_border = false;
      }

      $('#vetoResultModal').modal('show');
  });
}

function saveVeto(){

  var vetoNewValue = $('#veto_input').val();
  var vetoReason = $('#vetoreason_input').val();  
  var vetoDispo = $('#veto_dispo').val();

  if(vetoReason == ''){
    alert('Veto resultaat reden kan niet leeg zijn');
    return;
  }

  if(vetoDispo == 'null' && selected_veto_border === true ){
    alert('Voor grens-reactie dient u een uitslag type te selecteren voor veto-resultaten!');
    return;
  }

  $.ajax({
    type: "POST",
    data: { said: selected_veto_said, parameter: selected_veto_parameter, value: vetoNewValue, reason: vetoReason, disposition: vetoDispo},
    url: "{LB}/vetoResults/saveVeto"
  }).done(function() {
    selected_veto_said = false;
    $('#vetoResultModal').modal('hide');
    loadSelectedSample(selected_sample);
  });
}

function removeVeto(){

  $.ajax({
    type: "POST",
    data: { said: selected_veto_said, parameter: selected_veto_parameter},
    url: "{LB}/vetoResults/removeVeto"
  }).done(function() {
    selected_veto_said = false;
    $('#vetoResultModal').modal('hide');
    loadSelectedSample(selected_sample);
  });
}

function useNote(noteId){

  $.ajax({
    type: "POST",
    url: "{LB}/projectNotes/fetchNote/" + noteId
  }).done(function(note) {
    $('#projNotes').insertAtCaret(note);
    saveProjectNotes();
  });
}

function saveNote(){
  $.ajax({
    type: "POST",
    data: { noteTitle: $('#noteTitle').val(), noteContent: $('#noteContent').val()},
    url: "{LB}/projectNotes/addNote"
  }).done(function(){
    $('#noteTitle').val('');
    $('#noteContent').val('');
    $('#noteAddModal').modal('hide');
    updateNotes();
  });
}

function removeNote(note){

  console.log('remove:' + note);

  $.ajax({
    type: "POST",
    url: "{LB}/projectNotes/removeNote/" + note
  }).done(function(){
    updateNotes();
  });
}

function updateNotes(){
  $.ajax({
    type: "POST",
    url: "{LB}/projectNotes/listNote"
  }).done(function(msg){
    $('#projectNoteSelector').empty().append(msg);
    $("#projectNoteSelector").val("NULL");
  });
}

function exportProject(){
  window.location.replace("{LB}/exports/project/" + selected_project );
}

function prelimExportProject(){
  window.location.replace("{LB}/exports/tempProject/" + selected_project );
}

function dataExportProject(){
  window.location.replace("{LB}/dataMining/export/" + selected_project ); 
}

function fileSender(){
  window.location.replace("{LB}/exports/fileSender/" + selected_project ); 
}

function saveProjectNotes(){

  var notes = $('#projNotes').val();
  var selectedSample =$('#projectSampleNoteSelector').val()

  $.ajax({
    type: "POST",
    data: { project_notes: notes},
    url: "{LB}/projects/saveProjectNotes/" + selected_project + '/' + selectedSample
  }).done(function(options) {    
  });
}



function saveEditNote(){

  $('#noteEditModal').modal('hide');

  var noteId = $('#editNoteId').val();
  var noteName = $('#editNoteTitle').val();
  var noteContent = $('#editNoteContent').val();

  $.ajax({
    type: "POST",
    data: { name: noteName, content: noteContent},
    url: "{LB}/projectNotes/editNote/" + noteId
  }).done(function(options) {    
    updateNotes();
  });

}

function followProject(){
  $.ajax({
    type: "POST",
    url: "{LB}/bookmarks/follow/" + selected_project + "/PROJECT"
  }).done(function(options) {
    $('#projectFollow').attr('following', 1);
    $('#projectFollowIcon').removeClass('icon-bookmark-empty');
    $('#projectFollowIcon').addClass('icon-bookmark');
    $('#projectFollowIcon').addClass('icon-red');
  });
}

function unfollowProject(){
  $.ajax({
    type: "POST",
    url: "{LB}/bookmarks/unFollow/" + selected_project + "/PROJECT"
  }).done(function(options) {
    $('#projectFollowIcon').removeClass('icon-red');
    $('#projectFollowIcon').removeClass('icon-bookmark');
    $('#projectFollowIcon').addClass('icon-bookmark-empty');
    $('#projectFollow').attr('following', 0);
  });
}

function goToProjectPage(){
  window.location.href = "{LB}/labtalk/project/" + selected_project;
}

function checkClientWishes(client){
  //checkForWishesAndFiles
  $.ajax({
    type: "POST",
    dataType: "json",
    url: "{LB}/clients/checkForWishesAndFiles/" + client
  }).done(function(wishMsg) {
    if(wishMsg['wishes'] == true){
      $('#wishAlertDiv').show();
    } else{
      $('#wishAlertDiv').hide();
    }
  });
}

function openClientWishes(){
  $.ajax({
    type: "POST",
    dataType: "json",
    url: "{LB}/clients/renderWishesAndFilesDialog/" + selected_client
  }).done(function(wishMsg) {
    $('#clientWishesNote').html(wishMsg['wishes']);
    $('#clientWishesFileList').html(wishMsg['files']);
    $('#clientWishesModal').modal('show');
  });
}

function loadSampleNote(projectId, sampleId){

  $.ajax({
    type: "POST",
    dataType: "json",
    url: "{LB}/projects/loadSampleNote/" + projectId + "/" + sampleId
  }).done(function(sampleNote) {
      $('#projNotes').val(sampleNote['note']);
  });

}


function saveMetadata(){

  var metaName = $('#metadataName').val();
  var metaValue = $('#metadataValue').val(); 

  $.ajax({
      url: "{LB}/metadata/postSave" ,
      data: {sample: selected_sample, name: metaName, value: metaValue},
      type: "POST",      
  }).done(function(msg) {
    $('#metadataModal').modal('hide');
    $('#metadataName').val('');
    $('#metadataValue').val(''); 
    loadSelectedSample(selected_sample);
  });
}

function checkClientIsPortalUser(clientId){
  $.ajax({
    type: "POST",
    dataType: "json",
    url: "{LB}/portal/checkClientIsPortalUser/" + clientId
  }).done(function(msg) {
    
    if(msg.users > 0)
    {
      $('#portalClientIcon').addClass('icon-dark-green');
      $('#portalClientIcon').removeClass('icon-red');
    }

    else
    {
      $('#portalClientIcon').addClass('icon-red');
      $('#portalClientIcon').removeClass('icon-dark-green');
    }
    
  });  
}

function doChangeProjectClient(){  

    var targetClient = switch_project_client_to;

    //$('#client_change_ready').hide(); 
    $('#client_change_loading').show();

    $('#projectChangeClientModal').modal('hide');
    $('#projectBeingChangedModal').modal('show');


    $.ajax({
      type: "POST",      
      dataType : 'json',
      data : {        
        projectId : selected_project,
        targetClient : targetClient,
        reportStrategy : $('#change_project_report_strategy').val(),
      },
      url: "{LB}/projects/changeClientForProject"
    }).done(function(msg) {
    
      
      //$('#client_change_ready').show(); 

      if(msg['status'] == 'ok')
      {
        $('#projectBeingChangedModal').modal('hide');
        loadSelectedProject(targetClient, selected_project);
      }

      else
      {
        $('#projectBeingChangedModal').modal('hide');
        alert('er ging iets mis tijdens het wijzigen van de klant, controleer het project aub handmatig');
      }

      
      
    });
}

function loadSampleFiles(selected_sample){

  console.log('Should load sample files ')

  $.ajax({
    type: "POST",
    dataType: "json",
    url: "{LB}/sampleFiles/listFiles/" + selected_sample
  }).done(function(msg) {
    
  
    $('#sampleFilesDiv').html(msg['table']);
    $('#files_found_badge').html(msg['nfiles']);

  });

}


function toggleClientVis(event, selected_file, target_vis){

  event.preventDefault(); 



  if (project_authorised != false)  
  {
    var random_check = Math.floor(Math.random() * (2000 - 1000 + 1) + 1000);

    bootbox.prompt("<h3>Zichtbaarheid wijzigen?</h3> <p>Dit bestand is deel van een monster in een geautoriseerd project. Wijzigen van zichtbaarheid zal er voor zorgen dat de zichtbaarheid-status direct wordt overgenomen doorde portal </p> <p> Bevestigings code:<span class='label label-warning'>" + random_check + "</span></p>", function(result,) {
        if (result != null) {
            if (result == random_check) {
              doToggleClientVis(selected_file, target_vis);
            } else {
                bootbox.alert("<h3>Error</h3> <p> Niet gewijzigd, bevestigings code correct? </p> ");
            }
        }
    });
  }

  else
  {
    doToggleClientVis(selected_file, target_vis);
  }
  

}

function doToggleClientVis(selected_file, target_vis)
{
  $.ajax({
    type: "POST",
    url: "{LB}/sampleFiles/toggleClientVis/" + selected_file + "/" + target_vis
  }).done(function(msg) {
    loadSelectedSample(selected_sample);
  });

}



function dropFile(sampleFileId)
{

  if (project_authorised != false)  
  {

    var random_check = Math.floor(Math.random() * (2000 - 1000 + 1) + 1000);

    bootbox.prompt("<h3>Bestand verwijderen?</h3> <p>Deze verwijder actie wordt ook uitgevoerd op de portal, indien dit bestand zichtbaar is voor de gebruiker </p> <p> Bevestigings code:<span class='label label-warning'>" + random_check + "</span></p>", function(result) {
        if (result != null) {
            if (result == random_check) {
              doDropFile(sampleFileId);
            } else {
                bootbox.alert("<h3>Error</h3> <p> Niet gewijzigd, bevestigings code correct? </p> ");
            }
        }
    });

    

  }

  else
  {

    //confirm
    bootbox.confirm("<h3> Bestand verwijderen? </h3> <p>Wilt u dit bestand verwijderen?</p>", function(result) {
      if(result == true){
        doDropFile(sampleFileId);
      }
    });

  }

  
}

function doDropFile(sampleFileId)
{
  $.ajax({
    type: "POST",
    url: "{LB}/sampleFiles/dropFile/" + sampleFileId
  }).done(function(msg) {
    loadSelectedSample(selected_sample);
  });
}

</script>
