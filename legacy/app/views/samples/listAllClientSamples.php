<div class="span10">
    <div class="well">

      <table class="table" id="sampleListTable">
          <thead>
          <tr>
              <th>ID</th>
              <th>Barcode</th>
              <th>Project</th>
              <th>Monster omschrijving</th>
              <th>Monster details</th>
              <th>Inzet datum</th>

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

  $('#sampleListTable').DataTable( {
     "aaSorting": []
  });


});

</script>
