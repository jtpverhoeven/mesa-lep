<tr class="datarow" style="background-color: {rowColour}">
	<td >
		<input type="checkbox" class="" project="{projectTickName}" name="buffer_{id}" id="buffer_{id}" client="{client}" assayid="{project}" style="zoom: 1.5;" value="{id}" innoctarget="{tht_date}" >
	</td>
	<td><i class="icon {icon}"></i></td>
	<td>{client_name}</td>
	<td>{project}</td>
	<td>{project_name}</td>
	<td>{portal_follow_no}</td>
	<td>{sampling_date}</td>
	<td>{sampling_name}</td>
	<td>{sample_name}</td>
	<td>{sample_details}</td>	
	<td style="{celstyle}">{tht_date}</td>	
	<td><a onclick="seeMeta('{id}');">{meta}</a></td>
</tr>

<tr class="addrow"  style="background-color: {rowColour}">
	<td colspan="12"><strong>Analyses:</strong> {import_requested_analysis}{analyses_selected} <strong>Overige informatie: </strong>{misc_directions}</td>
</tr>
	