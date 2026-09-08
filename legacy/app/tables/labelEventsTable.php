<table class="table table-bordered table-condensed table-striped">    
    <thead>
        <tr>
            <th>ID</th>
            <th>{MESA_LBD_EVENTTYPE}</th>
            <th>{MESA_LBD_CONDITION}</th>
            <th>{MESA_LBD_LABELTOPRINT}</th>
            <th>{MESA_LBD_LABELPRINTTO}</th>
            <th>{MESA_UGA_COPYNUMBER}</th>
            <th></th>
        </tr>
    </thead>
    
    <tbody>
        <tr>            
            <td>{id}</td>
            <td>{event_name}</td>
            <td>{event_description}</td>
            <td>{label_name}</td>
            <td>{printer_name}</td>
            <td>{copies} </td>
            <td>
                <button class="btn btn-mini btn-warning" onclick="removeLabelEvent('{id}');"><i class="icon-trash"></i></button>
                <button class="btn btn-mini btn-primary" onclick="loadEventModal('{event_group}', '{id}');"><i class="icon-edit"></i></button>
            </td>

        </tr>
    </tbody>
          
</table>