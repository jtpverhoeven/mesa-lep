<tr clientId="{client}" projectId="{id}">
    <td style="vertical-align: middle;"><span class="label {billed_flair} project-billed-status" data-project-id="{id}" data-billed="{billed_state}" onclick="toggleProjectBilled(this)" style="cursor: pointer;">{billed_status}</span></td>
    <td style="vertical-align: middle;"><span class="label {flair}">{status}</span></td>
    <td><a href="/projects/search/{id}" target="_blank">MAZ-L {reference}</a> <i class="icon-eye-open hidden"></i> </td>
    <td>{revision}</td>
    <td>{project_name} </td>
    <td>{no_samples} {automatic_rodac_flair} {rodac_flair} {legionella_flair}</td>
    <td>{bemonster}</td>
    <td>{ontvangst}</td>
    <td>{innoc}</td>
    <td style="text-align:right;"><button class="btn btn-mini btn-danger" onclick="dropProject('{id}')"><i class="icon-remove"></i></td>
</tr>
