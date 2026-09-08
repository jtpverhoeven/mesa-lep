<tr>
    <td style="width: 25px;">
        <input class="selectedReport" type="checkbox" checked="checked" value="{id}"/>
    </td>
    
    <td> 
        <a href="{LB}/projects/search/{project}" target="_blank"> {reference}.{revision}.{print_version}</a> <br />    
        {project_name}
    </td>

    <td>{real_date}</td>

    <td>
        {auth_date} <br />
        {temp_label}

    </td>

    <td style="width: 75px;">

        <button class="btn btn-mini btn-success" onclick="download({id})">
            <i class="icon icon-download"></i>
        </button>
        
        <button class="btn btn-mini btn-primary " onClick="view({id})">
            <i class="icon icon-eye-open"></i>
        </button>
    </td>
    
</tr>
