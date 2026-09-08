<div class="span10">
    <div class="well">

      <table class="table" id="projectListTable">
          <thead>
          <tr>
              <th>Status</th>
              <th>Referentie</th>
              <th>Type monsters </th>

              <th>Aangemaakt op </th>
              <th>Gereed verwacht</th>
              <th>Geauthoriseerd op</th>
              <th>Gerapporteerd op</th>


              <th style="text-align: right;">Acties</th>
          </tr>
          </thead>
          <tbody id="projectTableBody">
            {table}
          </tbody>
      </table>


    </div>
  </div>


<script>

$(function() {

  $('#projectListTable').DataTable( {
    "aaSorting": []
  });


});

</script>
