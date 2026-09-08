<div class="span10">
  <div class="well">
      {table}
  </div>
</div>


<script>

$(document).ready(function() {
    $('#runningConfTable').DataTable({
        "paging":   false,
        "info":     false,
        "order": [[ 3, "asc" ]]
    });
} );


</script>
