  
<table class="table table-condensed table-bordered" style="margin-top: 5px">
    <thead>
        <tr>
            <th>{MESA_PLU_ASSAYNAME}</th>
            <th>Eenheid</th>
            <th>{MESA_PLU_ASSAYRESULT}</th>
            <th>Status</th>
        </tr>
    </thead>


    <tbody>

        <tr brickId="resultLineNew">
            <td style="width:40%;" rowspan="{rowspan}">{assay}</td>
            <td style="width:15%;">{result_name}</td>
            <td>{result_value} <span class="pull-right {veto_hide}">
                     <auth:vetoResultButton>
                        <button class="btn btn-default btn-mini btn-veto" title="{MESA_PLU_TOOLTIPSETVETO}" said="{said}" parameter="{parameter}" originalValue="{currentValue}"   tabindex="-1"><i class="icon-edit-sign"></i></button>
                     </auth>
                   </span>
                   <br />
                   <i>Ref: {reference}</i>
                     </td>
             <td>
               <span class="pull-right {ready_hider}" style="margin-left: 5px;"><i class="icon icon-check"></i> </span>
               <span class="pull-right {conf_hider}"><i class="icon icon-{conf_icon}"></i> </span>
             </td>
        </tr>

        <tr brickId="resultLine">
            <td>{result_name}</td>
            <td>{result_value} <span class="pull-right {veto_hide}">
                    <auth:vetoResultButton>
                    <button class="btn btn-default btn-mini btn-veto" title="{MESA_PLU_TOOLTIPSETVETO}" said="{said}" parameter="{parameter}" originalValue="{currentValue}"  tabindex="-1"><i class="icon-edit-sign"></i></button>
                    </auth>
                    </td>
              <td>
                <span class="pull-right {ready_hider}" style="margin-left: 5px;"><i class="icon icon-check"></i> </span>
                <span class="pull-right {conf_hider}"><i class="icon icon-{conf_icon}"></i> </span>
              </td>
        </tr>
    </tbody>
</table>
