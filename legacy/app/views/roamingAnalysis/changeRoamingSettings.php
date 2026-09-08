{roaming_form}

<script>

    $(document).ready(function(){

      $(".numerical-only-filter").keypress( function(e) {
          if (e.altKey || e.ctrlKey || e.which<28) return true;
          return "0123456789".indexOf(String.fromCharCode(e.which)) >= 0;
      });

    })

</script>
