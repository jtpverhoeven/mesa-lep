<tr id="sample_{id}" sampleId="{id}" client="{client}" style="background-color: {rowColour}">
    <td><input type="checkbox" id="sample_box_{id}" sampleId="{id}" client="{client}" project="{project_id}" /></td>
    <td style="text-align: center;">{warning_note}</td>
    <td>{barcode}</td>
    <td><a href="{lb}/clients/show/{client}">{client_name}</a></td>
    <td>{description}</td>
    <td>{ontvangst}</td>
    <td>{ontvangst_tijd}</td>
    <td>{inzet_datum}</td>
</tr>

<tr id="sampleinstructions_{id}"  class="{show_requested_analysis}" sampleId="{id}" client="{client}" style="background-color: {rowColour}">
	<td colspan="8"><strong>Analyses:</strong> {import_requested_analysis}, <strong>Overige analyses/opmerkingen:</strong> {import_other_directions}</td>
</tr>
