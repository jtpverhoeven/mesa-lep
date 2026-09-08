<tr class="datarow" style="background: {color};">
	<td>
		<input type="checkbox" class="" name="buffer_{id}" id="buffer_{id}" client="{client}" project="{projectTickName}" style="zoom: 1.5;" value="{id}" >
	</td>
	<td><i class="icon {icon}"></i></td>
	<td>{client_name}</td>
	<td>{project}</td>
	<td>{project_name}</td>
	<td>{portal_follow_no}</td>
	<td>{sampling_date} tijd:{sampling_time}</td>
	<td>{sampling_name}</td>
	<td>{sample_name}</td>
	<td>{room_desc}</td>
    <td>{sample_details}</td>	    
	<td><a onclick="seeMeta('{id}');">{meta}</a></td>
</tr>

<tr class="addrow" style="background:  {color}">
	<td colspan="12"><strong>Analyses:</strong> {analyses_selected} <strong>Overige informatie:</strong>{misc_directions}</td>
</tr>
	 