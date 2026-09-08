<div class="well well-small">
    <h4><i class="icon-table"></i> Overview report 
        <button type="button" onClick="window.print();"  class="btn btn-primary btn-small pull-right"><i class="icon-print"></i> Print</button>
    </h4>
    <div>
                
        
    </div>
</div>

<div class="well well-small">
    <table style="width: 100%" class="">
        <tbody>
            <tr>
                <td style="width: 45%; vertical-align: top;">
                    <p>
                       <span class="label label-info"><i class="icon-suitcase"></i></span> {proj_name} <br/>
                       <span class="label label-info"><i class="icon-calendar-empty"></i></span> {proj_date} <br/>                    
                       <span class="label label-info"><i class="icon-beaker"></i></span> {samples_in_proj} <br/>                    
                    </p>
                    
                    <p>
                        {custom_project_fields}
                    </p>
                    
                </td>
                <td style="width: 45%; vertical-align: top;">                    
                    <p>
                        <span class="label label-info"><i class="icon-building"></i></span> {client_name} <br/>
                        <span class="label label-info"><i class="icon-compass"></i></span> {subclient_name} <br/>                    
                    </p>                    
                </td>
            </tr>
        </tbody>
    </table>        
</div>

    {result_contents}

