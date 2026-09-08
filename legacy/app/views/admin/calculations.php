<div class="span8">
    <div class="well">
        <h3> Berekenings scripts </h3>
        {calc_table}
    </div>
</div>


<div class="span2">
    <ul class="nav nav-list well follow-scroll">
        <li class="nav-header"> Berekeningen </li>
        <li><a href="#addCaclModal" role="button"  data-toggle="modal" id="addCaclModalModalClicker"><i class="icon-plus"></i> Script uploaden</a> </li>
        <li><a href="#" id="viewClick"><i class="icon-eye-open"></i> Bekijken </a> </li>
        <li><a href="#" id="removeClick"><i class="icon-remove"></i> Verwijderen </a> </li>
    </ul>
</div>

<div id="addCaclModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="addCaclModalTitle" aria-hidden="true">

    <form action="{LB}/admin/uploadCalc" method="POST" enctype="multipart/form-data">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="addCaclModalTitle">Berekening script uploaden</h3>
    </div>
    <div class="modal-body" id="addCaclModalBody" >
      <form enctype="multipart/form-data" action="{LB}/admin/uploadCalc" method="POST">
          <input id="calcFile" name="calcFile" type="file"> <br>
          <input id="calcFileButton" type="submit" value="Uploaden">
      </form>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">{MESA_ASE_CANCEL}</button>
    </div>
  </form>
</div>


<div id="viewInUseModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="viewInUseModalTitle" aria-hidden="true">

    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="viewInUseModalTitle">Berekening script actief in</h3>
    </div>
    <div class="modal-body" id="viewCalcUseBody" >

    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Sluiten</button>
    </div>
</div>

<script>
$(function(){

  $('#viewClick').click(function(){
     var selectedCalc = $('input[name=selectedCalc]:checked').val();
     if(selectedCalc !== undefined){
          window.location.href = '{LB}/admin/viewCalc/' + selectedCalc
     } else {
         alert('Geen berekening geselecteerd');
     }
  });

  $('#removeClick').click(function(){
     var selectedCalc = $('input[name=selectedCalc]:checked').val();
     if(selectedCalc !== undefined){
         bootbox.confirm("<h3>Berekening script verwijderen?</h3> <p>Weet u zeker dat u dit script wilt verwijderen, dit is een destructieve actie en kan niet ongedaan gemaakt worden?</p>", function(result) {
             if(result == true){
               removeCalc(selectedCalc);
             }
         });
     } else {
         alert('Geen berekening geselecteerd');
     }
  });
});

function removeCalc(calcFile){
  $.ajax({
      type: "POST",
      url: "{LB}/admin/removeCalc/" + calcFile
  }).done(function(confwindow) {
      location.reload();
  });
}

function showCalcUsage(calcFile){
  $.ajax({
      type: "POST",
      url: "{LB}/admin/checkWhereActive/" + calcFile
  }).done(function(confwindow) {
        $('#viewCalcUseBody').html(confwindow);
        $('#viewInUseModal').modal('show');
  });
}
</script>
