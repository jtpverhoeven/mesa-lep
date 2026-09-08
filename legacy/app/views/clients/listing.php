<div class="span8">
    <div class="well">
        <h3> {MESA_CLI_CLIENTS} <small>{char_selected} </small> </h3>
        <div class="pagination pagination-mini">
            <ul>
                <li><a href="{LP}/clients/listing/A">A</a></li>
                <li><a href="{LP}/clients/listing/B">B</a></li>
                <li><a href="{LP}/clients/listing/C">C</a></li>
                <li><a href="{LP}/clients/listing/D">D</a></li>
                <li><a href="{LP}/clients/listing/E">E</a></li>
                <li><a href="{LP}/clients/listing/F">F</a></li>
                <li><a href="{LP}/clients/listing/G">G</a></li>
                <li><a href="{LP}/clients/listing/H">H</a></li>
                <li><a href="{LP}/clients/listing/I">I</a></li>
                <li><a href="{LP}/clients/listing/J">J</a></li>
                <li><a href="{LP}/clients/listing/K">K</a></li>
                <li><a href="{LP}/clients/listing/L">L</a></li>
                <li><a href="{LP}/clients/listing/M">M</a></li>
                <li><a href="{LP}/clients/listing/N">N</a></li>
                <li><a href="{LP}/clients/listing/O">O</a></li>
                <li><a href="{LP}/clients/listing/P">P</a></li>
                <li><a href="{LP}/clients/listing/Q">Q</a></li>
                <li><a href="{LP}/clients/listing/R">R</a></li>
                <li><a href="{LP}/clients/listing/S">S</a></li>
                <li><a href="{LP}/clients/listing/T">T</a></li>
                <li><a href="{LP}/clients/listing/U">U</a></li>
                <li><a href="{LP}/clients/listing/V">V</a></li>
                <li><a href="{LP}/clients/listing/W">W</a></li>
                <li><a href="{LP}/clients/listing/X">X</a></li>
                <li><a href="{LP}/clients/listing/Y">Y</a></li>
                <li><a href="{LP}/clients/listing/Z">Z</a></li>
                <li><a href="{LP}/clients/listing/0-9">0-9</a></li>
                <li><a href="{LP}/clients/listing/SYM">{MESA_CLI_SYBMOLS}</a></li>
                <li><a href="{LP}/clients/listing/DELETED">Verwijderde klanten</a></li>
            </ul>
        </div>
        {client_table_paginated}


    </div>
</div>

<div class="span2">
    <ul class="nav nav-list well">
        <li class="nav-header">{MESA_CLI_CLIENTACTION}</li>
        <li><a href="#" id="showClientButton"><i class="icon-edit"></i> {MESA_CLI_SHOWSELECTED} </a> </li>

        <li class="nav-header">{MESA_CLI_OTHER}</li>
        <auth:clientdumpbuttons>
        <li>
            <a href="{LB}/clients/dump" ><i class="icon-download"></i> Lijst downloaden </a> </li>        
            <li><a href="{LB}/clients/dump_contact_lists" ><i class="icon-download"></i> Lijst downloaden (per rij) </a> </li>            
        </auth>

        <li><a href="{LB}/clients/dashboard" ><i class="icon-backward"></i> {MESA_CLI_BACKTODASH} </a> </li>

    </ul>
</div>






<script>
    $(function(){
      $('#showClientButton').click(function(){
            var selectedClient = $('input[name=selectedClient]:checked').val();
            if(selectedClient !== undefined){
                window.location.href = '{LB}/clients/show/' + selectedClient;
            } else {
                alert('{MESA_CLI_NONSELECTED}');
            }
      });
    });
</script>
