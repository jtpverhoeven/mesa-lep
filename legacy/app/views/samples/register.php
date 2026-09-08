<div class="span10">

  <div class="row-fluid" id="topRow">

    <div class="span3">
      <div class="well well-small">
        <h6><i class="icon-barcode"></i> Registreer inzetdatum en tijd monster</h6>
        <div class="control-group" id="barCG">
          <input class="input-block-level" type="text" placeholder="{MESA_SLU_SCANINFO}"  value="" id="barcodeEntry" />
        </div>
      </div>
    </div>

    <div class="span3">
      <div class="well well-small">
        <h6><i class="icon-barcode"></i> {MESA_SLU_LOOKUPTITLE}</h6>
        <div class="control-group input-append input-block-level" id="searchBarCG">
          <input class="" type="text" placeholder="{MESA_SLU_SCANINFO}"  value="" id="searchBarEntry" />
          <span class="add-on"><a href="#" id="clearSearchBar"><i class="icon-remove"></i></a></span>
        </div>
      </div>
    </div>

    <div class="span3">
      <div class="well well-small">
        <h6><i class="icon-beaker"></i> Type lijst</h6>
        <p> Toon de volgende monsters: <span><select id="listType" class="input-block-level">
          <option value="1">Standaard</option>
          <option value="2">Legionella</option>
          <option value="3">RODAC</option>
        </select></span></p>
      </div>

    </div>

    <div class="span3">
      <div class="well well-small" id="storageLocDiv">
        <h6><i class="icon-bullseye"></i>Traceerbaarheid</h6>
        <p> Bij scannen wordt opslag locatie ingesteld als: <span id="storageLocation"></span></p>
        <p> Bij scannen wordt afweegstation ingesteld als: <span id="dilutionLocation"></span></p>
      </div>

    </div>

  </div>

  <div class="row-fluid">
    <div class="span11">
      <div class="well well-small" style="overflow: hidden;"  id="tableWell">
        <table class="table table-bordered table-striped" id="sampleTable">
          <thead >
            <tr>
              <th>Monster #</th>
              <th style="text-align: center" colspan="2">Ontvangst <br /> Datum + tijd</th>
              <th style="text-align: center" colspan="2">Inzet <br />  datum + tijd</th>
              <th>Klant</th>
              <th>Omschrijving monster</th>
              <th>L</th>
              <th>S</th>
              <th>C</th>
              <th>ST</th>
              <th style="text-align: center">In bak <br />  vriezer</th>
              <th style="text-align: center">Afweegstation</th>
            </tr>
          </thead>
          <tbody id="tableBody">
          </tbody>
        </table>
      </div>
    </div>

    <div class="span1">
      <div>
        <button class="btn btn-scroll" onClick="scrollUp();"  style="width: 70px"> <i class="icon icon-chevron-up"></i></button>
      </div>
      <div>
        <button class="btn btn-scroll scrollButtonPullDown"  style="width: 70px" onClick="resetSearch();"> <i class="icon icon-refresh"></i>
        </button>
      </div>
      <div>
        <button class="btn btn-scroll scrollButtonPullDown"  style="width: 70px" onClick="openStorageDialog();"> <i class="icon icon-archive"></i></button>
      </div>

      <div>
        <button class="btn btn-scroll scrollButtonPullDown" style="width: 70px" onClick="openDilutionDialog();"> 
          <svg xmlns="http://www.w3.org/2000/svg" style="margin-left: -5px" width="1.5em" height="1.5em" viewBox="0 0 24 24"><path fill="currentColor" d="M12 3c-1.27 0-2.4.8-2.82 2H3v2h1.95L2 14c-.47 2 1 3 3.5 3s4.06-1 3.5-3L6.05 7h3.12c.33.85.98 1.5 1.83 1.83V20H2v2h20v-2h-9V8.82c.85-.32 1.5-.97 1.82-1.82h3.13L15 14c-.47 2 1 3 3.5 3s4.06-1 3.5-3l-2.95-7H21V5h-6.17C14.4 3.8 13.27 3 12 3m0 2a1 1 0 0 1 1 1a1 1 0 0 1-1 1a1 1 0 0 1-1-1a1 1 0 0 1 1-1m-6.5 5.25L7 14H4l1.5-3.75m13 0L20 14h-3l1.5-3.75Z"/></svg>
      
        </button>
      </div>


      <div>
        <button class="btn btn-scroll scrollButtonPullDown"  style="width: 70px" onClick="editSelectedSample();"> <i class="icon icon-pencil"></i></button>
      </div>
      <div>
        <button class="btn btn-scroll scrollButtonPullDown"  style="width: 70px" onClick="scrollDown();"> <i class="icon icon-chevron-down"></i></button>
      </div>
    </div>
  </div>
</div>




<div id="storageModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="storageModalTitle" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="storageModalTitle">Monster opslag</h3>
  </div>
  <div class="modal-body" id="" style="max-height: 500px;" >

    <p> Bij registeren van monster inzet datum, registreer ook vriezer bak: </p>
    
    <div style="control-group">
      <input type="text" id="storageLocationInput" value="" class="input-block-level" style="font-size:14pt;" />
    </div>

  

    <div>

      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('A');"> A </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('B');"> B </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('C');"> C </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('D');"> D </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('E');"> E </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('F');"> F </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('G');"> G </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('H');"> H </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('I');"> I </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('J');"> J </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('K');"> K </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('L');"> L </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('M');"> M </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('N');"> N </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('O');"> O </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('P');"> P </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('Q');"> Q </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('R');"> R </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('S');"> S </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('T');"> T </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('U');"> U </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('V');"> V </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('W');"> W </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('X');"> X </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('Y');"> Y </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('Z');"> Z </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('1');"> 1 </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('2');"> 2 </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('3');"> 3 </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('4');"> 4 </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('5');"> 5 </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('6');"> 6 </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('7');"> 7 </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('8');"> 8 </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('9');"> 9 </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setStorageLocation('0');"> 0 </button>

      <button class="btn btn-danger btn-scroll scrollButtonFreezerSelect" onClick="clearStorageLocation();"><i class="icon icon-remove-circle"></i> Geen</button>
      <button class="btn btn-success btn-scroll scrollButtonFreezerSelect" onClick="updateStorageLocation();"><i class="icon icon-tick"></i> Opslaan</button>

    </div>



  </div>

</div>

<div id="diluteModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="diluteModalTitle" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="diluteModalTitle">Afweeg station</h3>
  </div>
  <div class="modal-body" id="" style="max-height: 500px;" >

    <p> Bij registeren van monster inzet datum, registreer ook afweeg station: </p>
    
    <div style="control-group">
      <input type="text" id="dilutionLocationInput" value="" class="input-block-level" style="font-size:14pt;" />
    </div>

  

    <div>

      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('A');"> A </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('B');"> B </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('C');"> C </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('D');"> D </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('E');"> E </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('F');"> F </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('G');"> G </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('H');"> H </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('I');"> I </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('J');"> J </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('K');"> K </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('L');"> L </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('M');"> M </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('N');"> N </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('O');"> O </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('P');"> P </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('Q');"> Q </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('R');"> R </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('S');"> S </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('T');"> T </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('U');"> U </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('V');"> V </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('W');"> W </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('X');"> X </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('Y');"> Y </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('Z');"> Z </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('1');"> 1 </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('2');"> 2 </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('3');"> 3 </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('4');"> 4 </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('5');"> 5 </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('6');"> 6 </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('7');"> 7 </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('8');"> 8 </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('9');"> 9 </button>
      <button class="btn btn-scroll scrollButtonFreezerSelect" onClick="setDilutionLocation('0');"> 0 </button>

      <button class="btn btn-danger btn-scroll scrollButtonFreezerSelect" onClick="clearDilutionLocation();"><i class="icon icon-remove-circle"></i> Geen</button>
      <button class="btn btn-success btn-scroll scrollButtonFreezerSelect" onClick="updateDilutionLocation();"><i class="icon icon-tick"></i> Opslaan</button>

    </div>



  </div>

</div>

<div id="changeModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="changeModalTitle" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="changeModalModalTitle">Monster opslag wijzigen</h3>
  </div>
  <div class="modal-body" id="changeModalBody" >

    <div class="control-group" id="freezerCG">
      <label class="control-label" for="store">Vriezer bak</label>
      <div class="controls">
        <input id="store" name="store" type="text" class="input-block-level">
      </div>
    </div>

    <div class="control-group" id="dilutionCG">
      <label class="control-label" for="store">Afweeg station:</label>
      <div class="controls">
        <input id="edit_diluted_at" name="edit_diluted_at" type="text" class="input-block-level">
      </div>
    </div>

    <div class="control-group" id="innocCG">
      <label class="control-label" for="innoc">Datum (formaat: dd-mm-YYYY UU:mm)</label>
      <div class="controls">
        <input id="innoc" name="innoc" type="text" class="input-block-level">
      </div>
    </div>


  </div>

  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_SAD_CLOSE}</button>
  </div>

</div>

<div id="noteModal" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3>Monster notitie</h3>
  </div>
  <div class="modal-body" id="noteModalBody" >


  </div>
</div>


<script>

var listType = 1;
var listId = false;
var scrollLocation = 0;
var storageLocation = 0;
var dilutionLocation = 0; 
var selectedSample = false;
var selectedWasStarted = false;
var currentlyLoading = false;
var currentlyToppingUp = false;


function topupLoad(){
  if(currentlyToppingUp == true){
    loadList('topup')
  }
}


function openStorageDialog(){
  $('#storageModal').modal('show');  
}

function openDilutionDialog(){
  $('#diluteModal').modal('show');  
}

function jumpBack(){
  $('#barcodeEntry').focus();
}

function clearStorageLocation(){
  $("#storageLocationInput").val('');
}

function setStorageLocation(loc){    

  $("#storageLocationInput").val(function() {
    return this.value + loc;
  }); 


}

function clearDilutionLocation(){
  $("#dilutionLocationInput").val('');
}

function setDilutionLocation(loc){    

  $("#dilutionLocationInput").val(function() {
    return this.value + loc;
  }); 


}

function updateStorageLocation(){
  var loc = $('#storageLocationInput').val();
  storageLocation = loc; 
  $('#storageLocationInput').attr('placeholder', loc);
  $('#storageLocation').html(loc);
  $('#storageLocationInput').val('');
  $('#storageModal').modal('hide');
  

  $.ajax({
    type: "POST",
    data: {bin: loc},
    dataType: "json",
    url: "{LB}/samples/updateBinSetting/"
  });

  

  jumpBack();
}

function updateDilutionLocation(){
  var loc = $('#dilutionLocationInput').val();
  dilutionLocation = loc; 
  $('#dilutionLocationInput').attr('placeholder', loc);
  $('#dilutionLocation').html(loc);
  $('#dilutionLocationInput').val('');
  $('#diluteModal').modal('hide');
  

  $.ajax({
    type: "POST",
    data: {bin: loc},
    dataType: "json",
    url: "{LB}/samples/updateDilutionSetting/"
  });

  

  jumpBack();
}


function resetSearch(){
  $('#tableBody').html('');
  window.selectedSample = false;
  window.selectedWasStarted = false;
  resetScroll();
  loadList(0);
  jumpBack();
}

function listResize(){
  height = $(window).height() - $('#topRow').height() - 100;
  return height;
}

function scrollDown(){
  scrollLocation = scrollLocation + {scroll_speed};
  $('#tableWell').slimScroll({ scrollTo: scrollLocation + 'px' });
}

function scrollUp(){
  scrollLocation = scrollLocation - {scroll_speed};
  if(scrollLocation < 0){
    scrollLocation = 0;
  }
  $('#tableWell').slimScroll({ scrollTo: scrollLocation + 'px' });
}

function resetScroll(){
  scrollLocation = 0;
  $('#tableWell').slimScroll({ scrollTo: scrollLocation + 'px' });
}


function loadList(loadFrom){


  if(currentlyLoading == true){
    return;
  }

  window.currentlyToppingUp = true;
  currentlyLoading = true;
  var currentlyInList = [];

  $("#sampleTable tr").each(function() {
    currentlyInList.push($(this).attr('sampleid'));
  });



  $.ajax({
    type: "POST",
    dataType: "JSON",
    data: {currentlyInList: JSON.stringify(currentlyInList)},
    url: "{LB}/samples/loadRegisterList/" + loadFrom + '/0/' + listType + '/' + window.listId
  }).done(function(msg) {


    if(msg['n_results'] != 0){

      if(loadFrom == 'topup'){
        $('#tableBody').prepend(msg['html']);

        $.each( msg['toDelete'], function( key, value ) {
          $('#sample_' + value).remove();
        });


      } else{
        $('#tableBody').append(msg['html']);
      }


      if(loadFrom == 0 || loadFrom == '0' || loadFrom == 'topup'){
        window.listId = msg['firstListId'];
      }
    }


    updateListType();
    currentlyLoading = false;
    return;
  });
}

function editSelectedSample(){

  if(window.selectedSample == false){
    alert('Geen monster geselecteerd.');
  } else{
    if(window.selectedWasStarted == '0'){
      alert('Kan niet wijzigen. Dit monster is nog niet gescanned');
      $('#barcodeEntry').focus().select();
    } else{
      loadSelectedEditSample(window.selectedSample);
    }
  }
}


function loadSelectedEditSample(sampleId){

  $.ajax({
    type: "POST",
    data: {},
    dataType: "json",
    url: "{LB}/samples/editSampleListLoad/" + sampleId
  }).done(function (msg) {

    if(msg != false){
      $('#innoc').val(msg['time']);
      $('#store').val(msg['store']);
      $('#edit_diluted_at').val(msg['diluted_at']);
      $('#changeModal').modal('show');
    } else{
      alert('Ongeldig monster: niet gevonden of deel van geauthoriseerd project. ');
    }


  });
}

function saveSelectedEditSample(){

  var updateStorage = $('#store').val();
  var updateInnoc = $('#innoc').val();
  var diluted_at = $('#edit_diluted_at').val();
  var id = window.selectedSample;

  $.ajax({
    type: "POST",
    data: {id: id, innoc: updateInnoc, storage: updateStorage, diluted_at : diluted_at},
    dataType: "json",
    url: "{LB}/samples/editSampleListSave/"
  }).done(function(msg) {

    $("#sample_" + msg['id']).find('td').eq(3).html('<strong>' + msg['date'] + '</strong>');
    $("#sample_" + msg['id']).find('td').eq(4).html('<strong>' + msg['time'] + '</strong>');
    $("#sample_" + msg['id']).find('td').eq(11).html('<strong>' + msg['storage'] + '</strong>');
    $("#sample_" + msg['id']).find('td').eq(12).html('<strong>' + msg['diluted_at'] + '</strong>');
  });

}


function updateListType(){

  if(listType != '1'){
        
    $('#sampleTable th:nth-child(6)').hide();
    $('#sampleTable th:nth-child(7)').hide();
    $('#sampleTable th:nth-child(8)').hide();
    $('#sampleTable th:nth-child(9)').hide();
    $('#sampleTable th:nth-child(10)').hide();
    $('#sampleTable th:nth-child(11)').hide();
    $('#sampleTable td:nth-child(8)').hide();
    $('#sampleTable td:nth-child(9)').hide();
    $('#sampleTable td:nth-child(10)').hide();
    $('#sampleTable td:nth-child(11)').hide();
    $('#sampleTable td:nth-child(12)').hide();
    $('#sampleTable td:nth-child(13)').hide();
    $('#storageLocDiv').hide();

  } else{
    $('#sampleTable th:nth-child(6)').show();
    $('#sampleTable th:nth-child(7)').show();
    $('#sampleTable th:nth-child(8)').show();
    $('#sampleTable th:nth-child(9)').show();
    $('#sampleTable th:nth-child(10)').show();
    $('#sampleTable th:nth-child(11)').show();
    $('#sampleTable td:nth-child(8)').show();
    $('#sampleTable td:nth-child(9)').show();
    $('#sampleTable td:nth-child(10)').show();
    $('#sampleTable td:nth-child(11)').show();
    $('#sampleTable td:nth-child(12)').show();
    $('#sampleTable td:nth-child(13)').show();
    $('#storageLocDiv').show();
  }
}


$(function() {
  height = listResize();

  $('#tableWell').slimScroll({
    height: height,
    railVisible: false,
    railOpacity: 0
  });

  $('#tableWell').slimScroll().bind('slimscroll', function(e, pos){

    if(pos == 'bottom'){
      lastId = $( "#tableBody tr:last").attr('sampleId');
      loadList(lastId);
    }
  });

  $('#listType').on('change', function(){
    var listTypeNow = $(this).val();
    window.listType = listTypeNow;
    resetSearch();
  });


  loadList(0);
  window.currentlyToppingUp = true;

  (function topupLoadTimer() {
    setTimeout(function() {
      topupLoad();
      topupLoadTimer();
    }, 6000 );
  })();


  $('#storageLocation').html('{default_bin}');
  $('#storageLocationInput').attr('placeholder', '{default_bin}');
  storageLocation = '{default_bin}';


  $('#dilutionLocation').html('{default_dilution}');
  $('#dilutionLocationInput').attr('placeholder', '{default_dilution}');
  dilutionLocation = '{default_dilution}';



  $('#storageLocationInput').on('focus', function(){
    console.log('focused');
  });


  $('#tableBody').on('click',  'tr', function(){


    //remove previous yellow
    $('#tableBody tr.selectedRow').children('td,th').removeAttr('style');
    $('#tableBody tr.selectedRow').removeClass('selectedRow');

    //add to new
    $(this).children('td,th').css('background-color','#afafaf');
    $(this).addClass('selectedRow');

    thisSample = $(this).attr('sampleid');
    thisStarted = $(this).attr('started');

    if(window.selectedSample == thisSample){
      $(this).children('td,th').removeAttr('style');
      window.selectedSample = false;
      window.selectedWasStarted = false;
    } else{
      window.selectedSample = thisSample;
      window.selectedWasStarted = thisStarted;
    }
  });

  $('#barcodeEntry').focus();
  $('#barcodeEntry').keypress(function(event) { return event.keyCode != 13; });
  $('#barcodeEntry').bind('keydown', 'return', function(){
      sendInnoc(false);
  });


  $('#changeModal').on('hidden', function (){
    saveSelectedEditSample();
    jumpBack();
  });

  $('#clearSearchBar').on('click', function(){
    $('#searchBarEntry').val('');
    loadList(0);
  });

  $('#searchBarEntry').focus();
  $('#searchBarEntry').keypress(function(event) { return event.keyCode != 13; });

  $('#searchBarEntry').bind('keydown', 'return', function() {


    if(currentlyLoading == true){
      return
    }

    window.currentlyToppingUp = false;
    currentlyLoading = true;
    listType = $('#listType').val();
    barcode = $('#searchBarEntry').val();

    $.ajax({
      type: "POST",
      data: {},
      dataType: "json",
      url: "{LB}/samples/loadRegisterList/0/" + barcode + "/" + listType
    }).done(function (msg) {

      if(msg['barcode'] == 'BAR_WRONG'){
        $.playSound('{LP}/snd/scanDeny.wav');
        $('#searchBarCG').addClass('error');
      } else{
        $('#tableBody').html(msg['html']);
        $("#listType").val(msg['listType'] );
        window.listType = msg['listType'];
        updateListType();
        window.selectedSample = false;
        window.selectedWasStarted = false;

      }

      currentlyLoading = false;
    })
  });
  $('#barcodeEntry').focus();
});

function sendInnoc(overwrite){

    $('#barcodeEntry').prop('disabled', true);
    barcode = $('#barcodeEntry').val();
    $('#barcodeEntry').val(barcode);

    //bypass storage location if its not a normal samples
    //as apparantly those do not use storage
    var editStorageLocation = false;
    if(listType != 1){
      editStorageLocation = ''
    } else{
      editStorageLocation = storageLocation;
    }

    data = {storage: editStorageLocation, dilution_at : dilutionLocation};
    clearBar = true;

    if(overwrite == true){
      data.overwrite = true;
    }

    $.ajax({
      type: "POST",
      data: data,
      dataType: "json",
      url: "{LB}/samples/registerInnoculation/" + barcode
    }).done(function(msg) {

      if(msg['barcode'] == 'BAR_WRONG'){
        $.playSound('{LP}/snd/scanDeny.wav');
        $('#barCG').addClass('error');
        clearBar = false;
      }

      else if(msg['barcode'] == 'ALREADY_STARTED'){
        $.playSound('{LP}/snd/scanDeny.wav');
        clearBar = false;

        bootbox.confirm("<h3>Dit monster is al ingezet</h3> <p>Nieuwe waardes toch opslaan?</p>", function(result) {
          if(result == true){
            sendInnoc(true);
          }
        });

      }

      else if(msg['barcode'] == 'ALREADY_AUTHORIZED'){
        $.playSound('{LP}/snd/scanDeny.wav');
        clearBar = false;
        bootbox.alert("<h3>Deel van geautoriseerd project</h3> <p>Kan waardes niet wijzigen</p>");       
      }

      else{
        $.playSound('{LP}/snd/scan.wav');

        //scroll to
        $('#tableWell').slimScroll({ scrollTo: '0px' });

        var el = $("#sample_" + msg['id']); //set element ~?
        var elPosition = el.position();
        var scrollToPosition = elPosition.top - 75;

        $('#tableWell').slimScroll({ scrollTo: scrollToPosition + 'px' });
        $('#tableBody').find("*").removeAttr('style');
        $("#sample_" + msg['id']).children('td,th').css('background-color', '#FCFF96');
        $("#sample_" + msg['id']).find('td').eq(3).html('<strong>' + msg['date'] + '</strong>');
        $("#sample_" + msg['id']).find('td').eq(4).html('<strong>' + msg['time'] + '</strong>');
        $("#sample_" + msg['id']).find('td').eq(11).html('<strong>' + msg['storage'] + '</strong>');
        $("#sample_" + msg['id']).find('td').eq(12).html('<strong>' + msg['diluted_at'] + '</strong>');
        $("#sample_" + msg['id']).attr('started', '1');

        if(msg['id'] == window.selectedSample){
          window.selectedWasStarted = true;
        }
        $('#barCG').removeClass('error');
      }

      focusBar(clearBar);

    });
}

function focusBar(clear){
  $('#barcodeEntry').prop('disabled', false);

  if(clear == true){
    $('#barcodeEntry').val('');
  }

  $('#barcodeEntry').focus();
}

function noteDisplay(sample){
  $.ajax({
    type: "POST",
    dataType: "json",
    data: { sampleId: sample},
    url: "{LB}/samples/fetchNote"
  }).done(function(response) {
    $('#noteModalBody').html(response['note']);
    $('#noteModal').modal('show');
  });
}

$('#listType').get(0).selectedIndex = 0;
</script>
